<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\SupplierBooking;
use App\Models\SupplierConnection;
use App\Models\TicketingAttempt;
use App\Models\User;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlledTicketingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_admin_can_issue_mock_ticket_for_eligible_paid_booking_with_pnr(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking))->assertRedirect();

        $this->assertDatabaseHas('booking_tickets', ['booking_id' => $booking->id, 'status' => 'issued']);
    }

    public function test_staff_can_issue_mock_ticket_for_own_agency_booking(): void
    {
        [$booking] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $staff = User::query()->where('email', 'staff@ota.demo')->firstOrFail();

        $this->actingAs($staff)->post(route('staff.bookings.issue-ticket', $booking))->assertRedirect();

        $this->assertDatabaseHas('ticketing_attempts', ['booking_id' => $booking->id, 'status' => 'success']);
    }

    public function test_agent_cannot_issue_ticket(): void
    {
        [$booking] = $this->eligibleBookingForMock();
        $agent = User::query()->where('email', 'agent@ota.demo')->firstOrFail();

        $this->actingAs($agent)->post(route('admin.bookings.issue-ticket', $booking))->assertForbidden();
    }

    public function test_customer_cannot_issue_ticket(): void
    {
        [$booking] = $this->eligibleBookingForMock();
        $customer = User::factory()->create([
            'account_type' => AccountType::Customer,
            'current_agency_id' => null,
        ]);

        $this->actingAs($customer)->post(route('admin.bookings.issue-ticket', $booking))->assertForbidden();
    }

    public function test_cross_agency_ticketing_denied(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $foreignAgency = Agency::factory()->create();
        $foreignBooking = Booking::factory()->create([
            'agency_id' => $foreignAgency->id,
            'status' => BookingStatus::Paid,
            'payment_status' => 'paid',
            'pnr' => 'PNR999',
            'supplier_reference' => 'SUPP-999',
            'supplier_booking_status' => 'pending_ticketing',
        ]);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $foreignBooking))->assertForbidden();
    }

    public function test_unpaid_booking_cannot_be_ticketed(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock(['payment_status' => 'unpaid']);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking))->assertSessionHasErrors('ticketing');
    }

    public function test_booking_without_supplier_booking_or_pnr_cannot_be_ticketed(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        SupplierBooking::query()->where('booking_id', $booking->id)->delete();
        $booking->update(['pnr' => null, 'supplier_reference' => null, 'supplier_booking_status' => null]);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking))->assertSessionHasErrors('ticketing');
    }

    public function test_successful_mock_ticketing_creates_attempt_and_tickets(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking));

        $this->assertDatabaseHas('ticketing_attempts', ['booking_id' => $booking->id, 'status' => 'success']);
        $this->assertDatabaseHas('booking_tickets', ['booking_id' => $booking->id, 'provider' => SupplierProvider::Mock->value]);
    }

    public function test_successful_mock_ticketing_changes_booking_status_to_ticketed(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking));

        $fresh = $booking->fresh();
        $this->assertSame(BookingStatus::Ticketed, $fresh->status);
        $this->assertSame('ticketed', $fresh->ticketing_status);
    }

    public function test_successful_mock_ticketing_updates_supplier_booking_status_to_ticketed(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking));

        $this->assertDatabaseHas('supplier_bookings', [
            'booking_id' => $booking->id,
            'status' => 'ticketed',
        ]);
    }

    public function test_ticketing_never_runs_for_sabre_and_returns_not_supported(): void
    {
        [$booking, $admin] = $this->eligibleBookingForProvider(SupplierProvider::Sabre);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking))->assertSessionHasErrors('ticketing');

        $attempt = TicketingAttempt::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();
        $this->assertSame('not_supported', $attempt->status);
    }

    public function test_not_supported_attempt_recorded_safely(): void
    {
        [$booking, $admin] = $this->eligibleBookingForProvider(SupplierProvider::Pia);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking));

        $attempt = TicketingAttempt::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();
        $this->assertSame('not_supported', $attempt->status);
        $this->assertStringNotContainsString('token', strtolower((string) $attempt->error_message));
    }

    public function test_audit_log_created(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking));

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'action' => 'booking.tickets_issued',
        ]);
    }

    public function test_booking_status_log_created(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking));

        $this->assertTrue(
            BookingStatusLog::query()
                ->where('booking_id', $booking->id)
                ->where('to_status', BookingStatus::Ticketed->value)
                ->exists()
        );
    }

    public function test_duplicate_ticketing_prevented(): void
    {
        [$booking, $admin] = $this->eligibleBookingForMock();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.bookings.issue-ticket', $booking))->assertSessionHasErrors('ticketing');

        $attempts = TicketingAttempt::query()->where('booking_id', $booking->id)->count();
        $this->assertSame(1, $attempts);
    }

    /**
     * @param  array<string, mixed>  $bookingOverrides
     * @return array{0: Booking, 1: User}
     */
    protected function eligibleBookingForMock(array $bookingOverrides = []): array
    {
        return $this->eligibleBookingForProvider(SupplierProvider::Mock, $bookingOverrides);
    }

    /**
     * @param  array<string, mixed>  $bookingOverrides
     * @return array{0: Booking, 1: User}
     */
    protected function eligibleBookingForProvider(SupplierProvider $provider, array $bookingOverrides = []): array
    {
        $this->seed(OtaFoundationSeeder::class);
        $admin = User::query()->where('email', 'admin@ota.demo')->firstOrFail();
        $connection = SupplierConnection::query()
            ->where('agency_id', $admin->current_agency_id)
            ->where('provider', $provider)
            ->firstOrFail();

        $booking = Booking::factory()->create(array_merge([
            'agency_id' => $admin->current_agency_id,
            'status' => BookingStatus::Paid,
            'payment_status' => 'paid',
            'supplier' => $provider->value,
            'pnr' => 'PNR123',
            'supplier_reference' => 'SUPP123',
            'supplier_booking_status' => 'pending_ticketing',
        ], $bookingOverrides));

        $booking->passengers()->createMany([
            ['passenger_index' => 0, 'title' => 'Mr', 'first_name' => 'Ali', 'last_name' => 'Khan'],
            ['passenger_index' => 1, 'title' => 'Ms', 'first_name' => 'Sara', 'last_name' => 'Khan'],
        ]);

        SupplierBooking::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'supplier_connection_id' => $connection->id,
            'provider' => $provider->value,
            'supplier_reference' => 'SUPP123',
            'pnr' => 'PNR123',
            'status' => 'pending_ticketing',
            'raw_summary' => ['seeded' => true],
            'created_by' => $admin->id,
            'created_at_supplier' => now(),
        ]);

        return [$booking->fresh(), $admin];
    }
}
