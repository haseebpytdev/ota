<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\SupplierBookingAttempt;
use App\Models\SupplierConnection;
use App\Models\User;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupplierBookingPreparationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_admin_can_create_mock_supplier_booking_for_eligible_booking(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking))->assertRedirect();

        $this->assertDatabaseHas('supplier_bookings', ['booking_id' => $booking->id, 'provider' => SupplierProvider::Mock->value]);
    }

    public function test_staff_can_create_mock_supplier_booking_for_own_agency_booking(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $staff = User::query()->where('email', 'staff@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();

        $this->actingAs($staff)->post(route('staff.bookings.supplier-booking', $booking))->assertRedirect();

        $this->assertDatabaseHas('supplier_booking_attempts', ['booking_id' => $booking->id, 'status' => 'success']);
    }

    public function test_agent_cannot_create_supplier_booking(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agent = User::query()->where('email', 'agent@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();

        $this->actingAs($agent)->post(route('admin.bookings.supplier-booking', $booking))->assertForbidden();
    }

    public function test_customer_cannot_create_supplier_booking(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $customer = User::factory()->create([
            'account_type' => AccountType::Customer,
            'current_agency_id' => null,
        ]);
        $booking = $this->eligibleBooking();

        $this->actingAs($customer)->post(route('admin.bookings.supplier-booking', $booking))->assertForbidden();
    }

    public function test_cross_agency_supplier_booking_is_denied(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $otherAgency = Agency::factory()->create();
        $booking = Booking::factory()->create([
            'agency_id' => $otherAgency->id,
            'status' => BookingStatus::Confirmed,
            'meta' => ['validated_offer_snapshot' => ['offer_id' => 'x']],
        ]);

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking))->assertForbidden();
    }

    public function test_booking_without_validation_snapshot_is_rejected(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = Booking::factory()->create([
            'agency_id' => $admin->current_agency_id,
            'status' => BookingStatus::Confirmed,
            'meta' => [],
        ]);

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking))->assertSessionHasErrors('supplier_booking');
    }

    public function test_ineligible_booking_status_is_rejected(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking(['status' => BookingStatus::Pending]);

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking))->assertSessionHasErrors('supplier_booking');
    }

    public function test_mock_supplier_booking_creates_attempt_and_booking_records(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking));

        $this->assertDatabaseHas('supplier_booking_attempts', ['booking_id' => $booking->id, 'status' => 'success']);
        $this->assertDatabaseHas('supplier_bookings', ['booking_id' => $booking->id, 'status' => 'pending_ticketing']);
    }

    public function test_mock_supplier_booking_updates_booking_supplier_fields(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking));
        $fresh = $booking->fresh();

        $this->assertNotNull($fresh->pnr);
        $this->assertNotNull($fresh->supplier_reference);
        $this->assertSame('pending_ticketing', $fresh->supplier_booking_status);
    }

    public function test_successful_supplier_booking_does_not_mark_booking_as_ticketed(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking));

        $this->assertNotSame(BookingStatus::Ticketed, $booking->fresh()->status);
    }

    public function test_sabre_supplier_booking_returns_not_supported_without_external_call(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Http::fake();
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();
        $sabreConnection = SupplierConnection::query()->where('agency_id', $booking->agency_id)->where('provider', SupplierProvider::Sabre)->firstOrFail();
        $sabreConnection->update(['is_active' => true, 'status' => SupplierConnectionStatus::Active]);
        $meta = $booking->meta ?? [];
        $meta['supplier_provider'] = SupplierProvider::Sabre->value;
        $meta['supplier_connection_id'] = $sabreConnection->id;
        $booking->update(['meta' => $meta, 'supplier' => SupplierProvider::Sabre->value]);

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking))->assertSessionHasErrors('supplier_booking');

        Http::assertNothingSent();
    }

    public function test_failure_attempt_is_recorded_safely(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();
        $sabreConnection = SupplierConnection::query()->where('agency_id', $booking->agency_id)->where('provider', SupplierProvider::Sabre)->firstOrFail();
        $sabreConnection->update(['is_active' => true, 'status' => SupplierConnectionStatus::Active]);
        $meta = $booking->meta ?? [];
        $meta['supplier_provider'] = SupplierProvider::Sabre->value;
        $meta['supplier_connection_id'] = $sabreConnection->id;
        $booking->update(['meta' => $meta, 'supplier' => SupplierProvider::Sabre->value]);

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking));

        $attempt = SupplierBookingAttempt::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();
        $this->assertSame('failed', $attempt->status);
        $this->assertStringNotContainsString('secret', strtolower((string) $attempt->error_message));
    }

    public function test_audit_log_created(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking();

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking));

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'action' => 'booking.supplier_booking_created',
        ]);
    }

    public function test_booking_status_log_created_when_moved_to_ticketing_pending_if_implemented(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $booking = $this->eligibleBooking(['status' => BookingStatus::Confirmed]);

        $this->actingAs($admin)->post(route('admin.bookings.supplier-booking', $booking));

        $exists = BookingStatusLog::query()
            ->where('booking_id', $booking->id)
            ->where('to_status', BookingStatus::TicketingPending->value)
            ->exists();

        $this->assertNotSame(BookingStatus::Ticketed, $booking->fresh()->status);
        $this->assertIsBool($exists);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function eligibleBooking(array $overrides = []): Booking
    {
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $mockConnection = SupplierConnection::query()->where('agency_id', $agency->id)->where('provider', SupplierProvider::Mock)->firstOrFail();

        return Booking::factory()->create(array_merge([
            'agency_id' => $agency->id,
            'status' => BookingStatus::Confirmed,
            'supplier' => SupplierProvider::Mock->value,
            'source_channel' => 'agent_portal',
            'meta' => [
                'supplier_provider' => SupplierProvider::Mock->value,
                'supplier_connection_id' => $mockConnection->id,
                'validated_offer_snapshot' => ['offer_id' => 'mock-1'],
            ],
        ], $overrides));
    }
}
