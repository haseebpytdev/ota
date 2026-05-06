<?php

namespace Tests\Feature;

use App\Enums\MarkupRuleStatus;
use App\Enums\MarkupRuleType;
use App\Enums\MarkupValueType;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\MarkupRule;
use App\Models\User;
use App\Services\Pricing\PricingRuleService;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkupPricingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_admin_can_view_markup_rules(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();

        $this->actingAs($admin)->get('/admin/markups')->assertOk();
    }

    public function test_agency_admin_can_create_markup_rule(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();

        $this->actingAs($admin)->post('/admin/markups', [
            'name' => 'Test global markup',
            'rule_type' => MarkupRuleType::Global->value,
            'value' => 4.5,
            'value_type' => MarkupValueType::Percentage->value,
            'priority' => 90,
            'status' => MarkupRuleStatus::Active->value,
        ])->assertRedirect('/admin/markups');

        $this->assertDatabaseHas('markup_rules', [
            'name' => 'Test global markup',
            'status' => MarkupRuleStatus::Active->value,
        ]);
    }

    public function test_agency_admin_can_edit_own_markup_rule(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $rule = MarkupRule::query()->firstOrFail();

        $this->actingAs($admin)->patch('/admin/markups/'.$rule->id, [
            'name' => 'Updated rule',
            'rule_type' => $rule->rule_type->value,
            'value' => 8,
            'value_type' => $rule->value_type->value,
            'priority' => 50,
            'status' => MarkupRuleStatus::Active->value,
        ])->assertRedirect('/admin/markups');

        $rule->refresh();
        $this->assertSame('Updated rule', $rule->name);
    }

    public function test_agency_admin_cannot_edit_another_agency_markup_rule(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $otherAgency = Agency::factory()->create();
        $rule = MarkupRule::factory()->create(['agency_id' => $otherAgency->id]);

        $this->actingAs($admin)
            ->patch('/admin/markups/'.$rule->id, [
                'name' => 'Should fail',
                'rule_type' => MarkupRuleType::Global->value,
                'value' => 2,
                'value_type' => MarkupValueType::Percentage->value,
                'priority' => 100,
                'status' => MarkupRuleStatus::Active->value,
            ])
            ->assertForbidden();
    }

    public function test_staff_cannot_access_admin_markups(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $staff = User::query()->where('email', 'staff@aurora-sky-travel.demo')->firstOrFail();

        $this->actingAs($staff)->get('/admin/markups')->assertForbidden();
    }

    public function test_inactive_markup_rule_is_not_applied(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $service = app(PricingRuleService::class);

        MarkupRule::factory()->create([
            'agency_id' => $agency->id,
            'rule_type' => MarkupRuleType::Global,
            'value_type' => MarkupValueType::Fixed,
            'value' => 50000,
            'status' => MarkupRuleStatus::Inactive,
            'is_active' => false,
        ]);

        $result = $service->calculateMarkup($agency, ['base_fare' => 100000, 'carrier_code' => 'PK'], [
            'route' => 'LHE-DXB',
            'airline' => 'pk',
            'supplier' => 'mock',
            'source_channel' => 'public_guest',
        ]);

        $this->assertLessThan(50000, $result['admin_markup']);
    }

    public function test_global_route_and_airline_markup_rules_apply_to_public_booking(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Guest',
            'last_name' => 'User',
            'email' => 'guest@example.com',
            'phone' => '+923001112233',
            'country' => 'Pakistan',
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addDays(12)->toDateString(),
        ])->assertRedirect(route('booking.review'));

        $booking = Booking::query()->latest('id')->firstOrFail();
        $fare = $booking->fareBreakdown()->firstOrFail();
        $snapshot = $booking->meta['pricing_snapshot'] ?? [];
        $ruleTypes = collect($snapshot['applied_rules'] ?? [])->pluck('rule_type')->all();

        $this->assertContains(MarkupRuleType::Global->value, $ruleTypes);
        $this->assertContains(MarkupRuleType::Route->value, $ruleTypes);
        $this->assertContains(MarkupRuleType::Airline->value, $ruleTypes);
        $this->assertGreaterThan(0, (float) $fare->markup);
    }

    public function test_route_markup_applies_only_to_matching_route(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $service = app(PricingRuleService::class);

        $matched = $service->calculateMarkup($agency, ['base_fare' => 120000, 'carrier_code' => 'PK'], [
            'route' => 'LHE-DXB',
            'airline' => 'pk',
            'supplier' => 'mock',
            'source_channel' => 'public_guest',
        ]);
        $unmatched = $service->calculateMarkup($agency, ['base_fare' => 120000, 'carrier_code' => 'PK'], [
            'route' => 'KHI-JED',
            'airline' => 'pk',
            'supplier' => 'mock',
            'source_channel' => 'public_guest',
        ]);

        $this->assertGreaterThan($unmatched['route_markup'], $matched['route_markup']);
    }

    public function test_airline_markup_applies_only_matching_airline(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $service = app(PricingRuleService::class);

        $pk = $service->calculateMarkup($agency, ['base_fare' => 120000, 'carrier_code' => 'PK'], [
            'route' => 'LHE-DXB',
            'airline' => 'pk',
            'supplier' => 'mock',
            'source_channel' => 'public_guest',
        ]);
        $ek = $service->calculateMarkup($agency, ['base_fare' => 120000, 'carrier_code' => 'EK'], [
            'route' => 'LHE-DXB',
            'airline' => 'ek',
            'supplier' => 'mock',
            'source_channel' => 'public_guest',
        ]);

        $this->assertGreaterThan($ek['airline_markup'], $pk['airline_markup']);
    }

    public function test_agent_source_channel_rule_applies_to_agent_booking(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agentUser = User::query()->where('email', 'agent@aurora-sky-travel.demo')->firstOrFail();

        $this->actingAs($agentUser)->post('/agent/bookings', [
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addDays(16)->toDateString(),
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'dob' => now()->subYears(30)->toDateString(),
            'nationality' => 'PK',
            'email' => 'ali.customer@example.com',
            'phone' => '+923001112233',
            'country' => 'Pakistan',
        ])->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $snapshot = $booking->meta['pricing_snapshot'] ?? [];
        $types = collect($snapshot['applied_rules'] ?? [])->pluck('rule_type')->all();

        $this->assertContains(MarkupRuleType::Agent->value, $types);
        $this->assertGreaterThan(0, (float) ($booking->fareBreakdown?->fees ?? 0));
    }

    public function test_applied_rules_are_stored_on_fare_snapshot_meta(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Snapshot',
            'last_name' => 'Test',
            'email' => 'snap@example.com',
            'phone' => '+923001112233',
            'country' => 'Pakistan',
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addDays(12)->toDateString(),
        ])->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $snapshot = $booking->meta['pricing_snapshot'] ?? [];

        $this->assertIsArray($snapshot['applied_rules'] ?? null);
        $this->assertNotEmpty($snapshot['applied_rules'] ?? []);
    }

    public function test_public_guest_booking_uses_db_markup_pricing(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Public',
            'last_name' => 'Pricing',
            'email' => 'public@example.com',
            'phone' => '+923001112233',
            'country' => 'Pakistan',
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addDays(20)->toDateString(),
        ])->assertRedirect(route('booking.review'));

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertGreaterThan(0, (float) ($booking->fareBreakdown?->markup ?? 0));
    }

    public function test_agent_booking_uses_db_markup_pricing(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agentUser = User::query()->where('email', 'agent@aurora-sky-travel.demo')->firstOrFail();
        $agent = Agent::query()->where('user_id', $agentUser->id)->firstOrFail();

        $this->actingAs($agentUser)->post('/agent/bookings', [
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addDays(16)->toDateString(),
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Agent',
            'last_name' => 'Pricing',
            'dob' => now()->subYears(30)->toDateString(),
            'nationality' => 'PK',
            'email' => 'agent.customer@example.com',
            'phone' => '+923001112233',
            'country' => 'Pakistan',
        ])->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame($agent->id, $booking->agent_id);
        $this->assertGreaterThan(0, (float) ($booking->fareBreakdown?->markup ?? 0));
    }
}
