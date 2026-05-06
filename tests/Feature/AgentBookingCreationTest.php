<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\User;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentBookingCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_access_agent_bookings_index(): void
    {
        $agentUser = $this->seededAgentUser();

        $this->actingAs($agentUser)
            ->get('/agent/bookings')
            ->assertOk();
    }

    public function test_agent_can_create_booking_request(): void
    {
        $agentUser = $this->seededAgentUser();

        $response = $this->actingAs($agentUser)->post('/agent/bookings', $this->validPayload());

        $booking = Booking::query()->first();
        $response->assertRedirect(route('agent.bookings.show', $booking, false));
        $this->assertNotNull($booking);
    }

    public function test_created_booking_has_agent_id_and_agent_portal_source_channel(): void
    {
        $agentUser = $this->seededAgentUser();
        $agent = Agent::query()->where('user_id', $agentUser->id)->firstOrFail();

        $this->actingAs($agentUser)->post('/agent/bookings', $this->validPayload());

        $booking = Booking::query()->firstOrFail();

        $this->assertSame($agent->id, $booking->agent_id);
        $this->assertSame('agent_portal', $booking->source_channel);
        $this->assertSame('mock', $booking->supplier);
    }

    public function test_created_booking_becomes_pending(): void
    {
        $agentUser = $this->seededAgentUser();

        $this->actingAs($agentUser)->post('/agent/bookings', $this->validPayload());

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(BookingStatus::Pending, $booking->status);
    }

    public function test_agent_can_view_own_booking(): void
    {
        $agentUser = $this->seededAgentUser();

        $this->actingAs($agentUser)->post('/agent/bookings', $this->validPayload());
        $booking = Booking::query()->firstOrFail();

        $this->actingAs($agentUser)
            ->get('/agent/bookings/'.$booking->id)
            ->assertOk();
    }

    public function test_agent_cannot_view_another_agents_booking(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'asif-travels')->firstOrFail();
        $agentUser = User::query()->where('email', 'agent@ota.demo')->firstOrFail();

        $otherAgentUser = User::factory()->agent()->create([
            'current_agency_id' => $agency->id,
        ]);
        $agency->users()->attach($otherAgentUser->id, ['role' => 'agent']);
        $otherAgent = Agent::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $otherAgentUser->id,
        ]);

        $booking = Booking::factory()->create([
            'agency_id' => $agency->id,
            'agent_id' => $otherAgent->id,
            'source_channel' => 'agent_portal',
            'status' => BookingStatus::Pending,
            'payment_status' => 'unpaid',
            'booking_reference' => 'OTA-OTHER-AGENT',
        ]);

        $this->actingAs($agentUser)
            ->get('/agent/bookings/'.$booking->id)
            ->assertForbidden();
    }

    public function test_agent_cannot_access_admin_bookings(): void
    {
        $agentUser = $this->seededAgentUser();

        $this->actingAs($agentUser)
            ->get('/admin/bookings')
            ->assertForbidden();
    }

    public function test_agency_admin_can_see_agent_created_booking_in_admin_bookings(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agentUser = User::query()->where('email', 'agent@ota.demo')->firstOrFail();
        $adminUser = User::query()->where('email', 'admin@ota.demo')->firstOrFail();

        $this->actingAs($agentUser)->post('/agent/bookings', $this->validPayload());
        $booking = Booking::query()->firstOrFail();

        $this->actingAs($adminUser)
            ->get('/admin/bookings')
            ->assertOk()
            ->assertSee($booking->booking_reference, false)
            ->assertSee('agent', false);
    }

    public function test_reports_count_agent_created_booking_as_agent_sales(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agentUser = User::query()->where('email', 'agent@ota.demo')->firstOrFail();
        $adminUser = User::query()->where('email', 'admin@ota.demo')->firstOrFail();

        $this->actingAs($agentUser)->post('/agent/bookings', $this->validPayload());

        $this->actingAs($adminUser)
            ->get('/admin/reports')
            ->assertOk()
            ->assertViewHas('summary', function (array $summary): bool {
                return $summary['agent_sales'] > 0
                    && (float) $summary['direct_customer_sales'] < 0.01;
            });
    }

    protected function seededAgentUser(): User
    {
        $this->seed(OtaFoundationSeeder::class);

        return User::query()->where('email', 'agent@ota.demo')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function validPayload(): array
    {
        return [
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
            'agent_note' => 'Customer requested morning departure.',
        ];
    }
}
