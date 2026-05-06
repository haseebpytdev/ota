<?php

namespace App\Policies;

use App\Models\AgencyCommunicationSetting;
use App\Models\User;

class AgencyCommunicationSettingPolicy
{
    public function view(User $user, AgencyCommunicationSetting $setting): bool
    {
        return $user->isPlatformAdmin()
            || ($user->current_agency_id === $setting->agency_id && ($user->isAgencyAdmin() || $user->isStaff()));
    }

    public function update(User $user, AgencyCommunicationSetting $setting): bool
    {
        return $user->isPlatformAdmin()
            || ($user->current_agency_id === $setting->agency_id && $user->isAgencyAdmin());
    }
}
