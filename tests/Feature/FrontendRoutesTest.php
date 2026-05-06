<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_responds(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Flight availability is subject to provider confirmation.', false);
    }

    /** @see README — client demo navigation checklist */
    public function test_client_demo_navigation_primary_paths_respond(): void
    {
        $this->seed(OtaFoundationSeeder::class);

        $depart = now()->addWeek()->format('Y-m-d');

        foreach ([
            '/',
            '/request-demo',
            '/flights/search',
            '/flights/results?from=LHE&to=DXB&depart='.$depart,
            '/booking/passengers?flight_id=mock-1&from=LHE&to=DXB&depart='.$depart,
        ] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/booking/confirmation')->assertRedirect(route('flights.search'));

        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $this->actingAs($admin);

        foreach ([
            '/admin',
            '/admin/bookings',
            '/admin/agents',
            '/admin/staff',
            '/admin/markups',
            '/admin/api-settings',
            '/admin/roles-permissions',
            '/admin/reports',
            '/admin/settings/branding',
            '/admin/go-live-checklist',
        ] as $path) {
            $this->get($path)->assertOk();
        }

        $staff = User::query()->where('email', 'staff@aurora-sky-travel.demo')->firstOrFail();
        $this->actingAs($staff);
        $this->get('/staff')->assertOk();

        $agent = User::query()->where('email', 'agent@aurora-sky-travel.demo')->firstOrFail();
        $this->actingAs($agent);
        $this->get('/agent')->assertOk();

        $customer = User::factory()->create([
            'account_type' => AccountType::Customer,
            'current_agency_id' => null,
        ]);
        $this->actingAs($customer);
        $this->get('/customer')->assertOk();

        $this->get('/flights/details/mock-1?from=LHE&to=DXB&depart='.$depart)->assertOk();
    }

    public function test_flight_search_and_results_flow(): void
    {
        $this->get('/flights/search')->assertOk();

        $depart = now()->addWeek()->format('Y-m-d');

        $this->get('/flights/results?from=NYC&to=LON&depart='.$depart)
            ->assertOk();

        $this->get('/flights/details/mock-1?from=NYC&to=LON&depart='.$depart)
            ->assertOk();
    }

    public function test_dashboard_routes_respond(): void
    {
        $this->seed(OtaFoundationSeeder::class);

        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $this->actingAs($admin);
        $this->get('/admin')->assertOk();

        $staff = User::query()->where('email', 'staff@aurora-sky-travel.demo')->firstOrFail();
        $this->actingAs($staff);
        $this->get('/staff')->assertOk();

        $agent = User::query()->where('email', 'agent@aurora-sky-travel.demo')->firstOrFail();
        $this->actingAs($agent);
        $this->get('/agent')->assertOk();

        $customer = User::factory()->create(['account_type' => AccountType::Customer]);
        $this->actingAs($customer);
        $this->get('/customer')->assertOk();
    }

    public function test_admin_section_routes_respond(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $this->actingAs($admin);

        foreach ([
            '/admin/bookings',
            '/admin/agents',
            '/admin/staff',
            '/admin/markups',
            '/admin/api-settings',
            '/admin/reports',
            '/admin/roles-permissions',
            '/admin/settings/branding',
            '/admin/go-live-checklist',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_admin_preview_query_routes_respond(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        Booking::factory()->for($agency)->create([
            'booking_reference' => 'OTA-99214',
            'status' => BookingStatus::Pending,
            'payment_status' => 'unpaid',
            'route' => 'LHE → DXB',
            'airline' => 'Demo',
            'supplier' => 'mock',
            'source_channel' => 'test',
        ]);

        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $this->actingAs($admin);

        $this->get('/admin/bookings?preview=OTA-99214')->assertOk()->assertSee('OTA-99214', false);
        $agent = Agent::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => User::factory()->create(['current_agency_id' => $agency->id, 'account_type' => AccountType::Agent])->id,
            'code' => 'AGT-9921',
        ]);
        $staffUser = User::factory()->create(['current_agency_id' => $agency->id, 'account_type' => AccountType::Staff]);
        $staff = StaffProfile::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $staffUser->id,
        ]);

        $this->get('/admin/agents?preview='.$agent->id)->assertOk()->assertSee('AGT-9921', false);
        $this->get('/admin/staff?preview='.$staff->id)->assertOk()->assertSee('STF-'.$staff->id, false);
    }

    public function test_booking_passengers_post_redirects_to_review(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+923001112233',
            'country' => 'Pakistan',
        ])->assertRedirect(route('booking.review'));
    }

    public function test_booking_review_renders_after_passenger_step(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+923001112233',
            'country' => 'Pakistan',
        ]);

        $this->get(route('booking.review'))
            ->assertOk()
            ->assertSee('Review your booking', false);
    }

    public function test_booking_review_post_redirects_to_confirmation(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+923001112233',
            'country' => 'Pakistan',
        ]);

        $this->post('/booking/review', [
            'booking_method' => 'bank_transfer',
        ])->assertRedirect(route('booking.confirmation'));
    }
}
