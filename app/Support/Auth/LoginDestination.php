<?php

namespace App\Support\Auth;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class LoginDestination
{
    public static function path(?User $user): string
    {
        if ($user === null || $user->account_type === null) {
            return self::adminPath();
        }

        return match ($user->account_type) {
            AccountType::PlatformAdmin, AccountType::AgencyAdmin => self::adminPath(),
            AccountType::Staff => Route::has('staff.dashboard') ? route('staff.dashboard', absolute: false) : '/staff',
            AccountType::Agent => Route::has('agent.dashboard') ? route('agent.dashboard', absolute: false) : '/agent',
            AccountType::Customer => Route::has('customer.dashboard') ? route('customer.dashboard', absolute: false) : '/customer',
        };
    }

    protected static function adminPath(): string
    {
        return Route::has('admin.dashboard') ? route('admin.dashboard', absolute: false) : '/admin';
    }
}
