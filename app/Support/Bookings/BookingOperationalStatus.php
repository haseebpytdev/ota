<?php

namespace App\Support\Bookings;

class BookingOperationalStatus
{
    /**
     * @return array{code: string, label: string, meaning: string}
     */
    public static function fromValues(
        string $status,
        ?string $paymentStatus = null,
        ?string $supplierBookingStatus = null,
        ?string $ticketingStatus = null,
        bool $hasPnr = false,
        ?string $cancellationStatus = null,
    ): array {
        $status = strtolower(trim($status));
        $paymentStatus = strtolower(trim((string) $paymentStatus));
        $supplierBookingStatus = strtolower(trim((string) $supplierBookingStatus));
        $ticketingStatus = strtolower(trim((string) $ticketingStatus));
        $cancellationStatus = strtolower(trim((string) $cancellationStatus));

        $code = match (true) {
            $status === 'expired' => 'expired',
            $status === 'failed' => 'failed',
            $cancellationStatus === 'requested' => 'cancel_requested',
            $status === 'cancelled' => 'cancelled',
            $status === 'completed' => 'completed',
            in_array($status, ['ticketed'], true) => 'ticketed',
            in_array($ticketingStatus, ['pending', 'ticketing_pending'], true) => 'ticketing_pending',
            in_array($supplierBookingStatus, ['created', 'pending_ticketing', 'booked'], true) || $hasPnr => 'supplier_booked',
            in_array($status, ['paid', 'payment_pending'], true) || in_array($paymentStatus, ['paid', 'partial'], true) => 'supplier_pending',
            $status === 'confirmed' => 'confirmed',
            in_array($status, ['pending', 'fare_review'], true) => 'pending',
            default => 'draft',
        };

        return [
            'code' => $code,
            'label' => self::label($code),
            'meaning' => self::meaning($code),
        ];
    }

    public static function label(string $code): string
    {
        return str_replace('_', ' ', $code);
    }

    public static function meaning(string $code): string
    {
        return match ($code) {
            'draft' => 'Booking request exists but not yet operationally confirmed.',
            'pending' => 'Awaiting payment/admin review.',
            'confirmed' => 'Admin accepted booking request and fare confirmed.',
            'supplier_pending' => 'Ready to create supplier booking/PNR.',
            'supplier_booked' => 'Supplier booking/PNR exists.',
            'ticketing_pending' => 'Paid + PNR exists, ticketing required.',
            'ticketed' => 'Ticket issued.',
            'completed' => 'Ticket/documents sent and no pending action.',
            'cancel_requested' => 'Cancellation requested.',
            'cancelled' => 'Booking cancelled.',
            'failed' => 'Supplier/payment/ticketing failure needing admin action.',
            'expired' => 'Fare/offer expired before booking completion.',
            default => 'Operational status unavailable.',
        };
    }
}
