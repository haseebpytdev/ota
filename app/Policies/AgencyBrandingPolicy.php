<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;

class AgencyBrandingPolicy
{
    public function view(User $user, Agency $agency): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ($user->isAgencyAdmin() || $user->isStaff())
            && $user->current_agency_id === $agency->id;
    }

    public function update(User $user, Agency $agency): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isAgencyAdmin() && $user->current_agency_id === $agency->id;
    }
}
