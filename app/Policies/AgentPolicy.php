<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            || $user->isAgencyAdmin()
            || $user->isStaff();
    }

    public function view(User $user, Agent $agent): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $agent->agency_id;
        }

        if ($user->isAgent()) {
            return $agent->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isAgencyAdmin();
    }

    public function update(User $user, Agent $agent): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isAgencyAdmin() && $user->current_agency_id === $agent->agency_id;
    }

    public function suspend(User $user, Agent $agent): bool
    {
        return $this->update($user, $agent);
    }
}
