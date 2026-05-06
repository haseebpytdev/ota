<?php

namespace App\Services\Suppliers\TicketingAdapters;

use App\Contracts\Suppliers\SupplierTicketingInterface;
use App\Data\TicketingResultData;
use App\Models\Booking;
use App\Models\SupplierBooking;
use App\Models\User;

class MockSupplierTicketingAdapter implements SupplierTicketingInterface
{
    public function issueTickets(Booking $booking, SupplierBooking $supplierBooking, User $actor): TicketingResultData
    {
        $tickets = [];
        foreach ($booking->passengers as $passenger) {
            $tickets[] = [
                'passenger_id' => $passenger->id,
                'passenger_name' => trim(implode(' ', array_filter([$passenger->title, $passenger->first_name, $passenger->last_name]))),
                'ticket_number' => '999-'.str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
                'pnr' => $supplierBooking->pnr ?: $booking->pnr,
                'airline_code' => $booking->meta['normalized_offer_snapshot']['airline_code'] ?? null,
                'issued_at' => now()->toIso8601String(),
            ];
        }

        return new TicketingResultData(
            success: true,
            status: 'issued',
            provider: $supplierBooking->provider,
            tickets: $tickets,
            safe_summary: [
                'ticket_count' => count($tickets),
                'simulated' => true,
            ],
            request_payload: [
                'booking_id' => $booking->id,
                'supplier_booking_id' => $supplierBooking->id,
            ],
            response_payload: [
                'status' => 'issued',
                'tickets' => $tickets,
            ],
        );
    }
}
