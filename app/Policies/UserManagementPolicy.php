<?php

namespace App\Policies;

use App\Enums\AccountType;
use App\Models\User;

class UserManagementPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isPlatformAdmin() || $actor->isAgencyAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        if ($actor->isPlatformAdmin()) {
            return true;
        }

        if ($actor->isAgencyAdmin()) {
            return $target->current_agency_id === $actor->current_agency_id
                && $target->account_type !== AccountType::PlatformAdmin;
        }

        return false;
    }

    public function create(User $actor): bool
    {
        return $actor->isPlatformAdmin() || $actor->isAgencyAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function suspend(User $actor, User $target): bool
    {
        return $this->update($actor, $target);
    }

    public function activate(User $actor, User $target): bool
    {
        return $this->update($actor, $target);
    }
}
