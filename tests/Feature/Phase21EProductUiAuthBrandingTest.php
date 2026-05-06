<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\User;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Book flights, track requests, submit payments, and download your travel documents.', false);

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

        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
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

    public function test_signup_dropdown_renders_customer_and_agent_options(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login', false)
            ->assertSee('Signup', false)
            ->assertSee('Customer Signup', false)
            ->assertSee('Agent Registration', false)
            ->assertSee('Book and manage your trips', false)
            ->assertSee('Apply for partner access', false)
            ->assertSee('Customer Login', false)
            ->assertSee('Agent Login', false)
            ->assertSee('Operator Login', false);
    }

    public function test_support_and_contact_pages_render_successfully(): void
    {
        $this->get('/support')
            ->assertOk()
            ->assertSee('Customer Support', false);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('Customer Support', false);
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
            ->assertSee('Customer, Agent, Staff, and Admin users can sign in here to access their dashboard.', false)
            ->assertSee('Customer Signup', false)
            ->assertSee('Agent Registration', false);
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

    public function test_public_pages_do_not_contain_demo_or_whitelabel_wording(): void
    {
        foreach (['/', '/flights/search', '/support', '/contact', '/agent/register'] as $path) {
            $response = $this->get($path)->assertOk();
            $response->assertDontSee('demo', false);
            $response->assertDontSee('white-label', false);
            $response->assertDontSee('mock', false);
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
}
