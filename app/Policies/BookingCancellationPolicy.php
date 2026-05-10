<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\BookingCancellationRequest;
use App\Models\User;

class BookingCancellationPolicy
{
    public function request(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $booking->agency_id;
        }

        if ($user->isAgent()) {
            $agent = $user->agent();

            return $agent !== null
                && $booking->agency_id === $user->current_agency_id
                && $booking->agent_id === $agent->id;
        }

        if ($user->isCustomer()) {
            return $booking->customer_id === $user->id;
        }

        return false;
    }

    public function approve(User $user, BookingCancellationRequest $request): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ($user->isAgencyAdmin() || $user->isStaff())
            && $user->current_agency_id === $request->agency_id;
    }

    public function reject(User $user, BookingCancellationRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    public function process(User $user, BookingCancellationRequest $request): bool
    {
        return $this->approve($user, $request);
    }
}
