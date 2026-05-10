<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAgent()) {
            return $user->agent() !== null;
        }

        return $user->account_type !== null;
    }

    public function view(User $user, Booking $booking): bool
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

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin()
            || $user->isAgencyAdmin()
            || $user->isStaff()
            || ($user->isAgent() && $user->agent() !== null);
    }

    public function update(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $booking->agency_id;
        }

        return false;
    }

    public function changeStatus(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $booking->agency_id;
        }

        return false;
    }

    public function addNote(User $user, Booking $booking): bool
    {
        if ($user->isAgent() || $user->isCustomer()) {
            return false;
        }

        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ($user->isAgencyAdmin() || $user->isStaff())
            && $user->current_agency_id === $booking->agency_id;
    }

    public function assignStaff(User $user, Booking $booking): bool
    {
        if ($user->isAgent() || $user->isCustomer() || $user->isStaff()) {
            return false;
        }

        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isAgencyAdmin()
            && $user->current_agency_id === $booking->agency_id;
    }

    public function createSupplierBooking(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $booking->agency_id;
        }

        return false;
    }

    public function recordPayment(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ($user->isAgencyAdmin() || $user->isStaff())
            && $user->current_agency_id === $booking->agency_id;
    }

    public function verifyPayment(User $user, Booking $booking): bool
    {
        return $this->recordPayment($user, $booking);
    }

    public function rejectPayment(User $user, Booking $booking): bool
    {
        return $this->recordPayment($user, $booking);
    }

    public function submitPaymentProof(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin() || $user->isAgencyAdmin() || $user->isStaff()) {
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

    public function issueTicket(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $booking->agency_id;
        }

        return false;
    }
}
