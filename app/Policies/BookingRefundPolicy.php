<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\BookingRefund;
use App\Models\User;

class BookingRefundPolicy
{
    public function create(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ($user->isAgencyAdmin() || $user->isStaff())
            && $user->current_agency_id === $booking->agency_id;
    }

    public function approve(User $user, BookingRefund $refund): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ($user->isAgencyAdmin() || $user->isStaff())
            && $user->current_agency_id === $refund->agency_id;
    }

    public function markPaid(User $user, BookingRefund $refund): bool
    {
        return $this->approve($user, $refund);
    }

    public function reject(User $user, BookingRefund $refund): bool
    {
        return $this->approve($user, $refund);
    }
}
