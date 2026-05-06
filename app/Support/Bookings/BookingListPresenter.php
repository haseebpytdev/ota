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
            'payment_status' => $booking->payment_status ?? 'unpaid',
            'created_at' => $booking->created_at?->format('Y-m-d H:i') ?? '',
            'contact_phone' => $contact?->phone ?? '—',
            'contact_email' => $contact?->email ?? '—',
            'internal_note' => $booking->notes ?: '—',
            'assigned_staff_name' => $booking->assignedStaff?->name,
        ];
    }
}
