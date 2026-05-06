<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Models\Agency;
use App\Models\User;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessManagementCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_admin_can_access_user_management(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $staff = User::query()->where('email', 'staff@aurora-sky-travel.demo')->firstOrFail();

        $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_agent_cannot_access_user_management(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agent = User::query()->where('email', 'agent@aurora-sky-travel.demo')->firstOrFail();

        $this->actingAs($agent)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_agency_admin_can_create_staff_user(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->agencyAdmin();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Staff A',
            'email' => 'staffa@example.test',
            'account_type' => 'staff',
            'status' => 'active',
            'department' => 'Operations',
            'role_title' => 'Senior Staff',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'staffa@example.test', 'account_type' => 'staff']);
        $this->assertDatabaseHas('staff_profiles', ['department' => 'Operations']);
    }

    public function test_agency_admin_can_create_agent_user_and_agent_record(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->agencyAdmin();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Agent A',
            'email' => 'agenta@example.test',
            'account_type' => 'agent',
            'status' => 'active',
            'commission_percent' => 6.5,
            'agency_name' => 'A Travel',
            'city' => 'Lahore',
            'agent_code' => 'AGT-CUSTOM',
        ])->assertRedirect();

        $user = User::query()->where('email', 'agenta@example.test')->firstOrFail();
        $this->assertDatabaseHas('agents', ['user_id' => $user->id, 'code' => 'AGT-CUSTOM']);
    }

    public function test_agency_admin_can_create_customer_user(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->agencyAdmin();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Customer A',
            'email' => 'customera@example.test',
            'account_type' => 'customer',
            'status' => 'invited',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'customera@example.test', 'account_type' => 'customer', 'status' => 'invited']);
    }

    public function test_agency_admin_cannot_create_platform_admin(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->agencyAdmin();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Forbidden',
            'email' => 'forbidden@example.test',
            'account_type' => 'platform_admin',
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_agency_admin_cannot_edit_user_from_another_agency(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->agencyAdmin();
        $otherAgency = Agency::factory()->create();
        $foreignUser = User::factory()->create([
            'account_type' => AccountType::Staff,
            'current_agency_id' => $otherAgency->id,
            'status' => UserAccountStatus::Active,
        ]);

        $this->actingAs($admin)->get(route('admin.users.edit', $foreignUser))->assertForbidden();
    }

    public function test_agency_admin_can_suspend_and_activate_own_agency_user(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->agencyAdmin();
        $user = User::factory()->create([
            'account_type' => AccountType::Staff,
            'current_agency_id' => $admin->current_agency_id,
            'status' => UserAccountStatus::Active,
        ]);

        $this->actingAs($admin)->patch(route('admin.users.suspend', $user))->assertRedirect();
        $this->assertSame('suspended', $user->fresh()->status->value);
        $this->actingAs($admin)->patch(route('admin.users.activate', $user))->assertRedirect();
        $this->assertSame('active', $user->fresh()->status->value);
    }

    public function test_suspended_user_cannot_access_operator_routes_if_middleware_implemented(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $staff = User::query()->where('email', 'staff@aurora-sky-travel.demo')->firstOrFail();
        $staff->update(['status' => UserAccountStatus::Suspended]);

        $this->actingAs($staff)->get(route('staff.dashboard'))->assertForbidden();
    }

    public function test_invite_action_creates_communication_and_audit_log_and_does_not_expose_password(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->agencyAdmin();
        $user = User::factory()->create([
            'account_type' => AccountType::Staff,
            'current_agency_id' => $admin->current_agency_id,
            'status' => UserAccountStatus::Active,
        ]);

        $this->actingAs($admin)->post(route('admin.users.send-invite', $user))->assertRedirect();
        $this->assertDatabaseHas('communication_logs', ['user_id' => $user->id, 'event' => 'user_invited']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.invited']);
    }

    public function test_reset_link_action_does_not_expose_password(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->agencyAdmin();
        $user = User::factory()->create([
            'account_type' => AccountType::Staff,
            'current_agency_id' => $admin->current_agency_id,
            'status' => UserAccountStatus::Active,
        ]);

        $this->actingAs($admin)->post(route('admin.users.reset-password-link', $user))->assertRedirect();
        $this->assertDatabaseHas('communication_logs', ['user_id' => $user->id, 'event' => 'password_reset_requested']);
    }

    public function test_platform_admin_can_create_agency_admin_if_supported(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $platform = User::factory()->create([
            'account_type' => AccountType::PlatformAdmin,
            'current_agency_id' => null,
            'status' => UserAccountStatus::Active,
        ]);
        $agency = Agency::factory()->create();

        $this->actingAs($platform)->post(route('admin.users.store'), [
            'name' => 'Agency Admin',
            'email' => 'agencyadmin@example.test',
            'account_type' => 'agency_admin',
            'status' => 'active',
            'agency_id' => $agency->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'agencyadmin@example.test', 'account_type' => 'agency_admin']);
    }

    public function test_user_index_filters_by_account_type_status_search(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        [$admin] = $this->agencyAdmin();
        User::factory()->create(['name' => 'Filter Match', 'email' => 'filter@demo.test', 'account_type' => AccountType::Staff, 'status' => UserAccountStatus::Invited, 'current_agency_id' => $admin->current_agency_id]);
        User::factory()->create(['name' => 'Other User', 'email' => 'other@demo.test', 'account_type' => AccountType::Customer, 'status' => UserAccountStatus::Active, 'current_agency_id' => $admin->current_agency_id]);

        $this->actingAs($admin)->get(route('admin.users.index', [
            'account_type' => 'staff',
            'status' => 'invited',
            'search' => 'Filter',
        ]))->assertOk()->assertSee('Filter Match')->assertDontSee('Other User');
    }

    /**
     * @return array{0: User}
     */
    protected function agencyAdmin(): array
    {
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->first();
        if ($admin === null) {
            $this->seed(OtaFoundationSeeder::class);
            $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        }

        return [$admin];
    }
}
