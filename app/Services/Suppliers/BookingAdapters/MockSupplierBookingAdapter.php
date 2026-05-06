<?php

namespace App\Services\Suppliers\BookingAdapters;

use App\Contracts\Suppliers\SupplierBookingInterface;
use App\Data\SupplierBookingResultData;
use App\Models\Booking;
use App\Models\SupplierConnection;
use App\Models\User;
use Illuminate\Support\Str;

class MockSupplierBookingAdapter implements SupplierBookingInterface
{
    public function createSupplierBooking(Booking $booking, SupplierConnection $connection, User $actor): SupplierBookingResultData
    {
        $pnr = strtoupper(Str::random(3)).random_int(100, 999);
        $supplierReference = 'MOCK-'.strtoupper(Str::random(8));

        return new SupplierBookingResultData(
            success: true,
            status: 'created',
            provider: $connection->provider->value,
            supplier_reference: $supplierReference,
            pnr: $pnr,
            safe_summary: [
                'booking_reference' => $booking->booking_reference,
                'route' => $booking->route,
                'simulated' => true,
            ],
            request_payload: [
                'booking_id' => $booking->id,
                'action' => 'create_pnr',
            ],
            response_payload: [
                'status' => 'created',
                'supplier_reference' => $supplierReference,
                'pnr' => $pnr,
            ],
        );
    }
}
