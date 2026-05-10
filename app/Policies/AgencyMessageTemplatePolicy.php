<?php

namespace App\Policies;

use App\Models\AgencyMessageTemplate;
use App\Models\User;

class AgencyMessageTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isAgencyAdmin() || $user->isStaff();
    }

    public function view(User $user, AgencyMessageTemplate $template): bool
    {
        return $user->isPlatformAdmin()
            || ($user->current_agency_id === $template->agency_id && ($user->isAgencyAdmin() || $user->isStaff()));
    }

    public function update(User $user, AgencyMessageTemplate $template): bool
    {
        return $user->isPlatformAdmin()
            || ($user->current_agency_id === $template->agency_id && $user->isAgencyAdmin());
    }
}
