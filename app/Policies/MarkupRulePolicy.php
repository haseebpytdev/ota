<?php

namespace App\Policies;

use App\Models\MarkupRule;
use App\Models\User;

class MarkupRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            || $user->isAgencyAdmin()
            || $user->isStaff();
    }

    public function view(User $user, MarkupRule $markupRule): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $markupRule->agency_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isAgencyAdmin();
    }

    public function update(User $user, MarkupRule $markupRule): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isAgencyAdmin() && $user->current_agency_id === $markupRule->agency_id;
    }
}
