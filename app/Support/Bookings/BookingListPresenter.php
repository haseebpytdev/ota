<?php

namespace App\Support\Bookings;

use App\Models\Booking;

class BookingListPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toListRow(Booking $booking): array
    {
        $booking->loadMissing(['passengers', 'contact', 'fareBreakdown', 'agent.user', 'assignedStaff']);

        $pax = $booking->passengers->first();
        $contact = $booking->contact;
        $fare = $booking->fareBreakdown;

        $customerName = $pax
            ? trim(implode(' ', array_filter([$pax->title, $pax->first_name, $pax->last_name])))
            : 'Guest';

        $previewQuery = $booking->booking_reference ?? (string) $booking->id;

        $markupFees = (float) ($fare?->markup ?? 0) + (float) ($fare?->fees ?? 0);

        $ctype = match ($booking->source_channel) {
            'agent_portal' => 'agent',
            'public_guest' => 'guest',
            default => 'guest',
        };
        $operational = BookingOperationalStatus::fromValues(
            $booking->status->value,
            (string) ($booking->payment_status ?? ''),
            (string) ($booking->supplier_booking_status ?? ''),
            (string) ($booking->ticketing_status ?? ''),
            ((string) ($booking->pnr ?? '')) !== '',
            (string) ($booking->cancellation_status ?? '')
        );
        $paymentOperational = PaymentOperationalStatus::fromValue((string) ($booking->payment_status ?? 'unpaid'));
        $supplierOperational = SupplierOperationalStatus::fromValues(
            (string) ($booking->supplier_booking_status ?? 'not_started'),
            (string) (($booking->meta['supplier_provider'] ?? null) ?: ($booking->supplier ?? '')),
            ((string) ($booking->pnr ?? '')) !== ''
        );
        $ticketingOperational = TicketingOperationalStatus::fromValues(
            (string) ($booking->ticketing_status ?? 'not_started'),
            (string) ($booking->payment_status ?? 'unpaid'),
            ((string) ($booking->pnr ?? '')) !== '',
            $booking->tickets()->exists(),
            (string) (($booking->meta['supplier_provider'] ?? null) ?: ($booking->supplier ?? '')),
            (string) ($booking->cancellation_status ?? '')
        );

        return [
            'id' => $booking->id,
            'booking_ref' => $booking->booking_reference ?? '',
            'preview_query' => $previewQuery,
            'customer_name' => $customerName,
            'customer_type' => $ctype,
            'agent_name' => $booking->agent?->user?->name,
            'route' => $booking->route ?? '—',
            'airline' => $booking->airline ?? '—',
            'travel_date' => $booking->travel_date?->format('Y-m-d') ?? '—',
            'passengers_count' => $booking->passengers->count(),
            'base_fare' => (int) round((float) ($fare?->base_fare ?? 0)),
            'markup' => (int) round($markupFees),
            'total_fare' => (int) round((float) ($fare?->total ?? 0)),
            'status' => $booking->status->value,
            'status_operational' => $operational['code'],
            'status_display' => $operational['label'],
            'status_meaning' => $operational['meaning'],
            'payment_status' => $booking->payment_status ?? 'unpaid',
            'payment_status_display' => $paymentOperational['label'],
            'payment_status_meaning' => $paymentOperational['meaning'],
            'supplier_status' => $booking->supplier_booking_status ?? 'not_started',
            'supplier_status_display' => $supplierOperational['label'],
            'supplier_status_meaning' => $supplierOperational['meaning'],
            'ticketing_status' => $booking->ticketing_status ?? 'not_started',
            'ticketing_status_display' => $ticketingOperational['label'],
            'ticketing_status_meaning' => $ticketingOperational['meaning'],
            'created_at' => $booking->created_at?->format('Y-m-d H:i') ?? '',
            'contact_phone' => $contact?->phone ?? '—',
            'contact_email' => $contact?->email ?? '—',
            'internal_note' => $booking->notes ?: '—',
            'assigned_staff_name' => $booking->assignedStaff?->name,
        ];
    }
}
