<?php

namespace App\Services\Payments;

use App\Enums\BookingRefundStatus;
use App\Enums\BookingStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingRefund;
use App\Models\CommunicationLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingRefundService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createRefund(Booking $booking, User $actor, array $data): BookingRefund
    {
        return DB::transaction(function () use ($booking, $actor, $data): BookingRefund {
            $this->assertRefundAmountAllowed($booking, (float) $data['amount']);

            $refund = BookingRefund::query()->create([
                'agency_id' => $booking->agency_id,
                'booking_id' => $booking->id,
                'booking_payment_id' => $data['booking_payment_id'] ?? null,
                'cancellation_request_id' => $data['cancellation_request_id'] ?? null,
                'amount' => (float) $data['amount'],
                'currency' => $data['currency'] ?? $booking->currency ?? 'PKR',
                'method' => $data['method'],
                'status' => BookingRefundStatus::Pending,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            $this->writeAudit($booking, $actor, 'booking.refund_created', [
                'booking_refund_id' => $refund->id,
                'amount' => (float) $refund->amount,
            ]);

            $this->writeCommunication($booking, $actor, 'refund_requested', [
                'booking_refund_id' => $refund->id,
                'amount' => (float) $refund->amount,
            ]);

            $this->recalculateBookingRefundStatus($booking);

            return $refund->fresh();
        });
    }

    public function approveRefund(BookingRefund $refund, User $actor): BookingRefund
    {
        return DB::transaction(function () use ($refund, $actor): BookingRefund {
            if ($refund->status !== BookingRefundStatus::Pending) {
                throw new InvalidArgumentException('Only pending refunds can be approved.');
            }

            $refund->forceFill([
                'status' => BookingRefundStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ])->save();

            $this->writeAudit($refund->booking, $actor, 'booking.refund_approved', [
                'booking_refund_id' => $refund->id,
            ]);

            $this->recalculateBookingRefundStatus($refund->booking);

            return $refund->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function markRefundPaid(BookingRefund $refund, User $actor, array $data): BookingRefund
    {
        return DB::transaction(function () use ($refund, $actor, $data): BookingRefund {
            if ($refund->status !== BookingRefundStatus::Approved) {
                throw new InvalidArgumentException('Only approved refunds can be marked paid.');
            }

            $meta = array_merge($refund->meta ?? [], $data['meta'] ?? []);
            $this->assertRefundAmountAllowed($refund->booking, (float) $refund->amount, $refund->id);
            $refund->forceFill([
                'status' => BookingRefundStatus::Paid,
                'paid_by' => $actor->id,
                'paid_at' => now(),
                'reference' => $data['reference'] ?? $refund->reference,
                'notes' => $data['notes'] ?? $refund->notes,
                'meta' => $meta,
            ])->save();

            $this->writeAudit($refund->booking, $actor, 'booking.refund_paid', [
                'booking_refund_id' => $refund->id,
                'amount' => (float) $refund->amount,
            ]);

            $this->writeCommunication($refund->booking, $actor, 'refund_paid', [
                'booking_refund_id' => $refund->id,
                'amount' => (float) $refund->amount,
            ]);

            $this->recalculateBookingRefundStatus($refund->booking);

            return $refund->fresh();
        });
    }

    public function rejectRefund(BookingRefund $refund, User $actor, string $reason): BookingRefund
    {
        return DB::transaction(function () use ($refund, $actor, $reason): BookingRefund {
            if (! in_array($refund->status, [BookingRefundStatus::Pending, BookingRefundStatus::Approved], true)) {
                throw new InvalidArgumentException('Only pending/approved refunds can be rejected.');
            }

            $meta = array_merge($refund->meta ?? [], ['rejection_reason' => $reason]);
            $refund->forceFill([
                'status' => BookingRefundStatus::Rejected,
                'notes' => trim(($refund->notes ? $refund->notes."\n" : '').'Rejected: '.$reason),
                'meta' => $meta,
            ])->save();

            $this->writeAudit($refund->booking, $actor, 'booking.refund_rejected', [
                'booking_refund_id' => $refund->id,
                'reason' => $reason,
            ]);

            $this->recalculateBookingRefundStatus($refund->booking);

            return $refund->fresh();
        });
    }

    public function recalculateBookingRefundStatus(Booking $booking): void
    {
        $booking->refresh();
        $verifiedPayments = (float) $booking->payments()->where('status', 'verified')->sum('amount');
        $paidRefunds = (float) $booking->refunds()->where('status', BookingRefundStatus::Paid)->sum('amount');
        $pendingRefunds = (float) $booking->refunds()->whereIn('status', [
            BookingRefundStatus::Pending,
            BookingRefundStatus::Approved,
        ])->sum('amount');
        $hasRejected = $booking->refunds()->where('status', BookingRefundStatus::Rejected)->exists();

        $refundStatus = null;
        if ($paidRefunds > 0 && $paidRefunds < max($verifiedPayments, 0.01)) {
            $refundStatus = 'partial';
        } elseif ($paidRefunds > 0 && $paidRefunds >= $verifiedPayments) {
            $refundStatus = 'refunded';
        } elseif ($pendingRefunds > 0) {
            $refundStatus = 'pending';
        } elseif ($hasRejected) {
            $refundStatus = 'rejected';
        }

        $booking->forceFill(['refund_status' => $refundStatus])->save();
        if ($refundStatus === 'refunded' && $booking->status === BookingStatus::Cancelled) {
            $booking->forceFill(['status' => BookingStatus::Refunded])->save();
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

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function writeCommunication(Booking $booking, User $actor, string $event, array $meta = []): void
    {
        CommunicationLog::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'user_id' => $booking->customer_id,
            'channel' => 'system',
            'event' => $event,
            'recipient_name' => $booking->customer?->name,
            'recipient_email' => $booking->contact?->email ?? $booking->customer?->email,
            'status' => 'logged',
            'meta' => array_merge($meta, ['actor_id' => $actor->id]),
            'sent_at' => now(),
        ]);
    }

    protected function assertRefundAmountAllowed(Booking $booking, float $candidateAmount, ?int $excludeRefundId = null): void
    {
        if ($candidateAmount <= 0) {
            throw new InvalidArgumentException('Refund amount must be greater than zero.');
        }

        if ((string) ($booking->refund_status ?? '') === 'refunded') {
            throw new InvalidArgumentException('Booking is already fully refunded.');
        }

        $verifiedPayments = (float) $booking->payments()->where('status', 'verified')->sum('amount');
        $approvedOrPaidRefunds = $booking->refunds()->whereIn('status', [BookingRefundStatus::Approved, BookingRefundStatus::Paid]);
        if ($excludeRefundId !== null) {
            $approvedOrPaidRefunds->where('id', '!=', $excludeRefundId);
        }
        $alreadyCommitted = (float) $approvedOrPaidRefunds->sum('amount');
        if (($alreadyCommitted + $candidateAmount) > $verifiedPayments) {
            throw new InvalidArgumentException('Refund amount cannot exceed verified paid amount.');
        }
    }
}
