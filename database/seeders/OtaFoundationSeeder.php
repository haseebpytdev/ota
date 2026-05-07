<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\MarkupRuleStatus;
use App\Enums\MarkupRuleType;
use App\Enums\MarkupValueType;
use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierEnvironment;
use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\MarkupRule;
use App\Models\StaffProfile;
use App\Models\SupplierConnection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OtaFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AirportAirlineReferenceSeeder::class);

        $agency = Agency::query()->updateOrCreate(
            ['slug' => 'asif-travels'],
            [
                'name' => 'Asif Travels',
                'timezone' => 'Asia/Karachi',
                'settings' => [
                    'domain' => 'ota.haseebasif.com',
                ],
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@ota.demo'],
            [
                'name' => 'Asif Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'current_agency_id' => $agency->id,
                'account_type' => AccountType::AgencyAdmin,
            ],
        );

        $staffUser = User::query()->updateOrCreate(
            ['email' => 'staff@ota.demo'],
            [
                'name' => 'Asif Staff',
                'username' => 'staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'current_agency_id' => $agency->id,
                'account_type' => AccountType::Staff,
            ],
        );

        $agentUser = User::query()->updateOrCreate(
            ['email' => 'agent@ota.demo'],
            [
                'name' => 'Asif Agent',
                'username' => 'agent',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'current_agency_id' => $agency->id,
                'account_type' => AccountType::Agent,
            ],
        );

        $customerUser = User::query()->updateOrCreate(
            ['email' => 'customer@ota.demo'],
            [
                'name' => 'Asif Customer',
                'username' => 'customer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'current_agency_id' => $agency->id,
                'account_type' => AccountType::Customer,
            ],
        );

        $admin->agencies()->syncWithoutDetaching([
            $agency->id => ['role' => 'agency_admin'],
        ]);
        $staffUser->agencies()->syncWithoutDetaching([
            $agency->id => ['role' => 'staff'],
        ]);
        $agentUser->agencies()->syncWithoutDetaching([
            $agency->id => ['role' => 'agent'],
        ]);
        $customerUser->agencies()->syncWithoutDetaching([
            $agency->id => ['role' => 'customer'],
        ]);

        StaffProfile::query()->updateOrCreate(
            [
                'agency_id' => $agency->id,
                'user_id' => $staffUser->id,
            ],
            [
                'job_title' => 'Operations Lead',
                'department' => 'Operations',
                'is_active' => true,
            ],
        );

        Agent::query()->updateOrCreate(
            [
                'agency_id' => $agency->id,
                'user_id' => $agentUser->id,
            ],
            [
                'code' => 'AGT-ASIF-001',
                'commission_percent' => 7.5,
                'is_active' => true,
                'meta' => ['tier' => 'gold'],
            ],
        );

        $this->seedSupplierConnections($agency);
        $this->seedMarkupRules($agency);
    }

    protected function seedSupplierConnections(Agency $agency): void
    {
        $definitions = [
            [
                'provider' => SupplierProvider::Mock,
                'name' => 'Mock Supplier',
                'environment' => SupplierEnvironment::Demo,
                'status' => SupplierConnectionStatus::Active,
                'is_active' => true,
                'settings' => ['mode' => 'fixture'],
            ],
            [
                'provider' => SupplierProvider::Sabre,
                'name' => 'Sabre',
                'environment' => SupplierEnvironment::Sandbox,
                'status' => SupplierConnectionStatus::Inactive,
                'is_active' => false,
                'settings' => [],
            ],
            [
                'provider' => SupplierProvider::Pia,
                'name' => 'PIA',
                'environment' => SupplierEnvironment::Sandbox,
                'status' => SupplierConnectionStatus::Inactive,
                'is_active' => false,
                'settings' => [],
            ],
            [
                'provider' => SupplierProvider::AirlineDirect,
                'name' => 'Airline Direct API',
                'environment' => SupplierEnvironment::Sandbox,
                'status' => SupplierConnectionStatus::Inactive,
                'is_active' => false,
                'settings' => [],
            ],
        ];

        foreach ($definitions as $row) {
            SupplierConnection::query()->updateOrCreate(
                [
                    'agency_id' => $agency->id,
                    'provider' => $row['provider'],
                ],
                [
                    'display_name' => $row['name'],
                    'name' => $row['name'],
                    'environment' => $row['environment'],
                    'status' => $row['status'],
                    'base_url' => null,
                    'credentials' => null,
                    'is_active' => $row['is_active'],
                    'last_tested_at' => $row['provider'] === SupplierProvider::Mock ? now() : null,
                    'last_test_status' => $row['provider'] === SupplierProvider::Mock ? 'success' : null,
                    'last_error' => null,
                    'settings' => $row['settings'],
                    'meta' => null,
                ],
            );
        }
    }

    protected function seedMarkupRules(Agency $agency): void
    {
        MarkupRule::query()->updateOrCreate(
            [
                'agency_id' => $agency->id,
                'name' => 'Global markup 5%',
            ],
            [
                'rule_type' => MarkupRuleType::Global,
                'value' => 5.0,
                'value_type' => MarkupValueType::Percentage,
                'applies_to' => null,
                'priority' => 100,
                'status' => MarkupRuleStatus::Active,
                'meta' => ['notes' => 'Default markup for all channels.'],
                'is_active' => true,
                'config' => null,
            ],
        );

        MarkupRule::query()->updateOrCreate(
            [
                'agency_id' => $agency->id,
                'name' => 'LHE-DXB fixed markup',
            ],
            [
                'rule_type' => MarkupRuleType::Route,
                'value' => 1200,
                'value_type' => MarkupValueType::Fixed,
                'applies_to' => ['route' => 'LHE-DXB'],
                'priority' => 20,
                'status' => MarkupRuleStatus::Active,
                'meta' => ['notes' => 'Popular route uplift.'],
                'is_active' => true,
                'config' => null,
            ],
        );

        MarkupRule::query()->updateOrCreate(
            [
                'agency_id' => $agency->id,
                'name' => 'PIA airline markup',
            ],
            [
                'rule_type' => MarkupRuleType::Airline,
                'value' => 2.5,
                'value_type' => MarkupValueType::Percentage,
                'applies_to' => ['airline' => 'pk'],
                'priority' => 30,
                'status' => MarkupRuleStatus::Active,
                'meta' => ['notes' => 'Carrier-specific adjustment.'],
                'is_active' => true,
                'config' => null,
            ],
        );

        MarkupRule::query()->updateOrCreate(
            [
                'agency_id' => $agency->id,
                'name' => 'Agent portal service fee',
            ],
            [
                'rule_type' => MarkupRuleType::Agent,
                'value' => 800,
                'value_type' => MarkupValueType::Fixed,
                'applies_to' => ['source_channel' => 'agent_portal'],
                'priority' => 40,
                'status' => MarkupRuleStatus::Active,
                'meta' => ['bucket' => 'service_fee', 'notes' => 'Agent channel service fee'],
                'is_active' => true,
                'config' => null,
            ],
        );
    }
}
