<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\BookingDocument;
use App\Models\User;

class BookingDocumentPolicy
{
    public function create(User $user, Booking $booking): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $booking->agency_id;
        }

        return false;
    }

    public function view(User $user, BookingDocument $document): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $document->agency_id;
        }

        if ($user->isCustomer()) {
            return $document->booking?->customer_id === $user->id;
        }

        return false;
    }
}
