<?php

namespace App\Policies;

use App\Models\SupplierConnection;
use App\Models\User;

class SupplierConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            || $user->isAgencyAdmin()
            || $user->isStaff();
    }

    public function view(User $user, SupplierConnection $supplierConnection): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() || $user->isStaff()) {
            return $user->current_agency_id === $supplierConnection->agency_id;
        }

        return false;
    }

    public function update(User $user, SupplierConnection $supplierConnection): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isAgencyAdmin() && $user->current_agency_id === $supplierConnection->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isAgencyAdmin();
    }
}
