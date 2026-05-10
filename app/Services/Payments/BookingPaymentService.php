<?php

namespace App\Services\Payments;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Services\Communication\BookingCommunicationService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingPaymentService
{
    public function __construct(
        protected BookingService $bookingService,
        protected BookingCommunicationService $communicationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordManualPayment(Booking $booking, User $actor, array $data): BookingPayment
    {
        return DB::transaction(function () use ($booking, $actor, $data): BookingPayment {
            $this->assertManualPaymentAllowed($booking, $actor, $data);
            $verifyNow = (bool) ($data['verify_now'] ?? true);
            $status = $verifyNow ? BookingPaymentStatus::Verified : BookingPaymentStatus::Pending;

            $payment = $booking->payments()->create([
                'agency_id' => $booking->agency_id,
                'payer_user_id' => $data['payer_user_id'] ?? $booking->customer_id,
                'received_by' => $actor->id,
                'payment_reference' => $data['payment_reference'] ?? null,
                'method' => $data['method'],
                'status' => $status,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $booking->currency ?? 'PKR',
                'proof_path' => $data['proof_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
                'verified_at' => $verifyNow ? now() : null,
                'meta' => $data['meta'] ?? null,
            ]);

            $this->writeAudit($booking, $actor, 'booking.payment_recorded', [
                'booking_payment_id' => $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $payment->method->value,
                'status' => $payment->status->value,
            ]);

            $this->recalculateBookingPaymentStatus($booking);

            return $payment->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitPaymentProof(Booking $booking, ?User $payer, array $data): BookingPayment
    {
        $payment = DB::transaction(function () use ($booking, $payer, $data): BookingPayment {
            return $booking->payments()->create([
                'agency_id' => $booking->agency_id,
                'payer_user_id' => $payer?->id ?? $booking->customer_id,
                'received_by' => null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'method' => $data['method'],
                'status' => BookingPaymentStatus::Submitted,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $booking->currency ?? 'PKR',
                'proof_path' => $data['proof_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
                'meta' => $data['meta'] ?? null,
            ]);
        });

        $this->communicationService->sendPaymentSubmitted($payment);

        return $payment;
    }

    public function verifyPayment(BookingPayment $payment, User $actor): BookingPayment
    {
        if ($payment->status === BookingPaymentStatus::Verified) {
            throw new InvalidArgumentException('Payment is already verified.');
        }

        $payment = DB::transaction(function () use ($payment, $actor): BookingPayment {
            $payment->forceFill([
                'status' => BookingPaymentStatus::Verified,
                'received_by' => $actor->id,
                'verified_at' => now(),
                'rejected_at' => null,
            ])->save();

            $this->writeAudit($payment->booking, $actor, 'booking.payment_verified', [
                'booking_payment_id' => $payment->id,
                'amount' => (float) $payment->amount,
            ]);

            $this->recalculateBookingPaymentStatus($payment->booking);

            return $payment->fresh();
        });

        $payment = $payment->fresh(['booking.contact', 'booking.customer', 'booking.agency']);
        $this->communicationService->sendPaymentVerified($payment);

        return $payment;
    }

    public function rejectPayment(BookingPayment $payment, User $actor, string $reason): BookingPayment
    {
        $payment = DB::transaction(function () use ($payment, $actor, $reason): BookingPayment {
            $payment->forceFill([
                'status' => BookingPaymentStatus::Rejected,
                'received_by' => $actor->id,
                'rejected_at' => now(),
                'meta' => array_merge($payment->meta ?? [], ['rejection_reason' => $reason]),
            ])->save();

            $this->writeAudit($payment->booking, $actor, 'booking.payment_rejected', [
                'booking_payment_id' => $payment->id,
                'reason' => $reason,
            ]);

            $this->recalculateBookingPaymentStatus($payment->booking);

            return $payment->fresh();
        });

        $payment = $payment->fresh(['booking.contact', 'booking.customer', 'booking.agency']);
        $this->communicationService->sendPaymentRejected($payment);

        return $payment;
    }

    public function recalculateBookingPaymentStatus(Booking $booking): void
    {
        $booking->refresh();
        $verifiedTotal = (float) $booking->payments()
            ->where('status', BookingPaymentStatus::Verified)
            ->sum('amount');
        $bookingTotal = (float) ($booking->fareBreakdown?->total ?? 0);
        $balanceDue = max(0, $bookingTotal - $verifiedTotal);

        $paymentStatus = 'unpaid';
        if ($verifiedTotal > 0 && $verifiedTotal < $bookingTotal) {
            $paymentStatus = 'partial';
        } elseif ($verifiedTotal >= $bookingTotal && $bookingTotal > 0) {
            $paymentStatus = 'paid';
        }

        $booking->forceFill([
            'amount_paid' => $verifiedTotal,
            'balance_due' => $bookingTotal > 0 ? $balanceDue : null,
            'payment_status' => $paymentStatus,
        ])->save();

        if ($paymentStatus === 'paid' && $booking->status === BookingStatus::PaymentPending) {
            if (($booking->supplier_booking_status ?? '') === 'pending_ticketing') {
                $this->bookingService->changeStatus($booking, BookingStatus::Paid, null, 'Payment fully verified');
                $this->bookingService->changeStatus($booking->fresh(), BookingStatus::TicketingPending, null, 'Ready for ticketing queue');
            } else {
                $this->bookingService->changeStatus($booking, BookingStatus::Paid, null, 'Payment fully verified');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertManualPaymentAllowed(Booking $booking, User $actor, array $data): void
    {
        $booking->refresh();
        $bookingTotal = (float) ($booking->fareBreakdown?->total ?? 0);
        $verifiedTotal = (float) $booking->payments()
            ->where('status', BookingPaymentStatus::Verified)
            ->sum('amount');
        $amount = (float) ($data['amount'] ?? 0);
        $balance = max(0, $bookingTotal - $verifiedTotal);
        $isCancelled = in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Refunded], true);
        if ($isCancelled) {
            throw new InvalidArgumentException('Manual payment is not allowed for cancelled/refunded bookings.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $adminOverride = (bool) ($data['admin_override'] ?? false);
        $canOverride = $actor->isAgencyAdmin() || $actor->isPlatformAdmin();
        if (! $adminOverride || ! $canOverride) {
            if ($balance <= 0) {
                throw new InvalidArgumentException('No payment balance is due on this booking.');
            }
            if ($amount > $balance) {
                throw new InvalidArgumentException('Overpayment is blocked unless admin override is enabled.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    protected function writeAudit(Booking $booking, User $actor, string $action, array $newValues): void
    {
        AuditLog::query()->create([
            'agency_id' => $booking->agency_id,
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'properties' => [
                'old_values' => [],
                'new_values' => $newValues,
            ],
        ]);
    }
}
