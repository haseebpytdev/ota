<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\AgencyMedia;
use App\Models\User;

class AgencyMediaPolicy
{
    public function viewAny(User $user, Agency $agency): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ($user->isAgencyAdmin() || $user->isStaff())
            && $user->current_agency_id === $agency->id;
    }

    public function create(User $user, Agency $agency): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isAgencyAdmin() && $user->current_agency_id === $agency->id;
    }

    public function delete(User $user, AgencyMedia $media): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isAgencyAdmin() && $user->current_agency_id === $media->agency_id;
    }
}
