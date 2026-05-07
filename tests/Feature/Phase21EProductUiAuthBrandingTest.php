<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\User;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicBookingPassengersPayload;
use Tests\TestCase;

class Phase21EProductUiAuthBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_asif_branding_fallback_text(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Asif Travels', false)
            ->assertDontSee('white-label', false)
            ->assertDontSee('Client preview', false);
    }

    public function test_auth_pages_render_branded_layout(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome to Asif Travels', false)
            ->assertSee('Sign in to Asif Travels', false);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Reset your password', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee('Create your Asif Travels account', false)
            ->assertSee('Book flights, track your booking requests, submit payment proof, and access travel documents from one place.', false);

        $this->get('/agent/register')
            ->assertOk()
            ->assertSee('Join the Asif Travels Agent Network', false)
            ->assertSee('Submit application', false)
            ->assertSee('Admin review', false)
            ->assertSee('Receive activation link', false);
    }

    public function test_customer_registration_creates_customer_account_and_redirects_customer_dashboard(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware([ValidateCsrfToken::class]);

        $response = $this->post('/register', [
            'first_name' => 'New',
            'last_name' => 'Customer',
            'email' => 'new.customer@example.com',
            'mobile' => '+923001234567',
            'security_check' => '5',
            'terms' => '1',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/customer');

        $user = User::query()->where('email', 'new.customer@example.com')->firstOrFail();
        $this->assertSame(AccountType::Customer, $user->account_type);
    }

    public function test_agent_application_route_stores_pending_application_without_creating_agent_account(): void
    {
        $this->withoutMiddleware([ValidateCsrfToken::class]);

        $response = $this->post('/agent/register', [
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'email' => 'ali.agent@example.com',
            'mobile' => '+923009998887',
            'company_name' => 'Khan Travels',
            'business_type' => 'Travel agency',
            'city' => 'Lahore',
            'country' => 'Pakistan',
            'office_address' => 'Main Boulevard, Lahore',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('agent.register.submitted'));
        $this->assertDatabaseHas('agent_applications', [
            'email' => 'ali.agent@example.com',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'ali.agent@example.com',
            'account_type' => AccountType::Agent->value,
        ]);
    }

    public function test_staff_and_admin_public_registration_routes_are_not_available(): void
    {
        $this->get('/register/staff')->assertNotFound();
        $this->get('/register/admin')->assertNotFound();
    }

    public function test_admin_can_approve_agent_application_and_create_agent_profile(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware([ValidateCsrfToken::class]);

        $application = AgentApplication::query()->create([
            'first_name' => 'Agent',
            'last_name' => 'Applicant',
            'email' => 'agent.applicant@example.com',
            'mobile' => '+923221112233',
            'company_name' => 'Applicant Travels',
            'business_type' => 'Travel agency',
            'city' => 'Karachi',
            'country' => 'Pakistan',
            'office_address' => 'Shahrah-e-Faisal, Karachi',
            'status' => 'pending',
        ]);

        $admin = User::query()->where('email', 'admin@ota.demo')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.agent-applications.approve', $application), [
            'internal_note' => 'Looks good',
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertNotNull($application->reviewed_at);
        $this->assertSame($admin->id, $application->reviewed_by);

        $agentUser = User::query()->where('email', 'agent.applicant@example.com')->firstOrFail();
        $this->assertSame(AccountType::Agent, $agentUser->account_type);
        $this->assertDatabaseHas('agents', ['user_id' => $agentUser->id]);
        $this->assertInstanceOf(Agent::class, Agent::query()->where('user_id', $agentUser->id)->first());
    }

    public function test_navbar_information_architecture_matches_final_public_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Home', false)
            ->assertSee('Flights', false)
            ->assertSee('Agent Network', false)
            ->assertSee('Support', false)
            ->assertSee('Contact', false)
            ->assertSee('Login', false)
            ->assertSee('Customer Login', false)
            ->assertSee('Agent Login', false)
            ->assertSee('Operator Login', false)
            ->assertSee('Signup', false)
            ->assertSee('Customer Signup', false)
            ->assertSee('Agent Registration', false)
            ->assertSee('Book Flights', false);
    }

    public function test_support_and_contact_pages_render_different_titles_and_content(): void
    {
        $this->get('/support')
            ->assertOk()
            ->assertSee('Customer Support', false)
            ->assertSee('Lookup Booking', false)
            ->assertDontSee('Contact Asif Travels', false);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('Contact Asif Travels', false)
            ->assertSee('Email Us', false)
            ->assertDontSee('Customer Support', false);
    }

    public function test_agent_application_form_contains_review_notice(): void
    {
        $this->get('/agent/register/apply')
            ->assertOk()
            ->assertSee('Agent applications are reviewed by Asif Travels. After approval, you will receive an activation email.', false)
            ->assertSee('Submit Agent Application', false);
    }

    public function test_agent_application_submitted_page_confirms_no_instant_access(): void
    {
        $this->get('/agent/register/submitted')
            ->assertOk()
            ->assertSee('Application submitted', false)
            ->assertSee('You will receive login access only after approval.', false);
    }

    public function test_login_page_contains_all_user_type_access_wording(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Customer', false)
            ->assertSee('View bookings and documents', false)
            ->assertSee('Agent', false)
            ->assertSee('Manage requests and commissions', false)
            ->assertSee('Operator', false)
            ->assertSee('Admin and staff access', false)
            ->assertSee('Customer signup', false)
            ->assertSee('Agent registration', false);
    }

    public function test_auth_and_signup_pages_do_not_show_demo_or_supplier_placeholders(): void
    {
        foreach (['/login', '/register', '/agent/register', '/agent/register/apply', '/agent/register/submitted'] as $path) {
            $response = $this->get($path)->assertOk();
            $response->assertDontSee('iati', false);
            $response->assertDontSee('demo', false);
            $response->assertDontSee('white-label', false);
            $response->assertDontSee('mock', false);
            $response->assertDontSee('placeholder', false);
        }
    }

    public function test_public_pages_do_not_contain_banned_or_technical_phrases(): void
    {
        foreach (['/', '/flights/search', '/support', '/contact', '/agent/register', '/flights/results?from=LHE&to=DXB&depart=2026-06-20&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0'] as $path) {
            $response = $this->get($path)->assertOk();
            $response->assertDontSee('demo', false);
            $response->assertDontSee('white-label', false);
            $response->assertDontSee('sample data', false);
            $response->assertDontSee('provider readiness', false);
            $response->assertDontSee('provider capabilities', false);
            $response->assertDontSee('inventory preview', false);
            $response->assertDontSee('API-ready supplier', false);
        }

        foreach (['/', '/flights/search', '/support', '/contact', '/agent/register'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('mock', false);
        }
    }

    public function test_flight_search_page_uses_standardized_heading_copy(): void
    {
        $this->get('/flights/search')
            ->assertOk()
            ->assertSee('Book your next flight', false)
            ->assertSee('ota-flight-search', false)
            ->assertSee('Search routes, compare fares, and continue to booking review with Asif Travels support.', false);
    }

    public function test_flight_results_and_review_use_consistent_cta_labels(): void
    {
        $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-20&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')
            ->assertOk()
            ->assertSee('Book Now', false);

        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware([ValidateCsrfToken::class]);
        $this->post('/booking/passengers', array_merge(
            PublicBookingPassengersPayload::merge([
                'flight_id' => 'mock-1',
                'offer_id' => 'mock-1',
                'from' => 'LHE',
                'to' => 'DXB',
                'depart' => now()->addWeek()->format('Y-m-d'),
                'email' => 'cta.user@example.com',
            ]),
            PublicBookingPassengersPayload::internationalDocuments(),
        ))->assertRedirect(route('booking.review'));

        $this->get('/booking/review')
            ->assertOk()
            ->assertSee('Request booking', false);
    }

    public function test_customer_register_page_support_text_is_not_duplicated(): void
    {
        $response = $this->get('/register')->assertOk();
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'Need help?'));
    }

    public function test_agent_landing_page_shows_card_timeline_workflow(): void
    {
        $this->get('/agent/register')
            ->assertOk()
            ->assertSee('How it works', false)
            ->assertSee('1. Submit application', false)
            ->assertSee('2. Admin review', false)
            ->assertSee('3. Receive activation link', false)
            ->assertSee('4. Start booking', false);
    }
}
