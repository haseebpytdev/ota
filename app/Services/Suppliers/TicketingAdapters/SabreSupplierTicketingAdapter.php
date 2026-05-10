<?php

namespace App\Services\Suppliers\TicketingAdapters;

use App\Contracts\Suppliers\SupplierTicketingInterface;
use App\Data\TicketingResultData;
use App\Models\Booking;
use App\Models\SupplierBooking;
use App\Models\User;

class SabreSupplierTicketingAdapter implements SupplierTicketingInterface
{
    public function issueTickets(Booking $booking, SupplierBooking $supplierBooking, User $actor): TicketingResultData
    {
        return new TicketingResultData(
            success: false,
            status: 'not_supported',
            provider: $supplierBooking->provider,
            warnings: ['Sabre ticketing is not implemented until ticketing API documentation is reviewed.'],
            safe_summary: ['reason' => 'api_docs_required'],
        );
    }
}
