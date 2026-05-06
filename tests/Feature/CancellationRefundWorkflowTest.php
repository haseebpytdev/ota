<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\UserAccountStatus;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingCancellationRequest;
use App\Models\BookingPayment;
use App\Models\BookingRefund;
use App\Models\GuestBookingAccessToken;
use App\Models\User;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationRefundWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_admin_can_request_approve_and_process_cancellation(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->seededUsers();
        $booking = $this->makeBooking($admin->current_agency_id, BookingStatus::Confirmed);

        $this->actingAs($admin)->post(route('admin.bookings.cancellations.store', $booking), [
            'cancellation_type' => 'booking_cancel',
            'reason' => 'Customer requested cancellation',
        ])->assertRedirect();

        $request = BookingCancellationRequest::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.bookings.cancellations.approve', $request))->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.bookings.cancellations.process', $request))->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status->value);
        $this->assertSame('processed', $booking->cancellation_status);
    }

    public function test_staff_can_process_own_agency_cancellation(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin, $staff] = $this->seededUsers();
        $booking = $this->makeBooking($admin->current_agency_id, BookingStatus::Pending);
        $request = BookingCancellationRequest::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'requested_by' => $admin->id,
            'request_source' => 'admin',
            'status' => 'approved',
            'cancellation_type' => 'booking_cancel',
        ]);

        $this->actingAs($staff)->patch(route('staff.bookings.cancellations.process', $request))->assertRedirect();
        $this->assertSame('processed', $request->fresh()->status->value);
    }

    public function test_agent_can_request_cancellation_for_own_booking_and_cannot_approve(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [, , $agentUser] = $this->seededUsers();
        $agentProfile = $agentUser->agent();
        $booking = $this->makeBooking($agentUser->current_agency_id, BookingStatus::Confirmed, null, $agentProfile?->id);

        $this->actingAs($agentUser)->post(route('agent.bookings.cancellations.store', $booking), [
            'cancellation_type' => 'booking_cancel',
        ])->assertRedirect();
        $request = BookingCancellationRequest::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();

        $this->actingAs($agentUser)->patch(route('admin.bookings.cancellations.approve', $request))->assertForbidden();
    }

    public function test_customer_can_request_cancellation_for_own_booking(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [, , , $customer] = $this->seededUsers();
        $booking = $this->makeBooking($customer->current_agency_id, BookingStatus::PaymentPending, $customer->id, null);

        $this->actingAs($customer)->post(route('customer.bookings.cancellations.store', $booking), [
            'cancellation_type' => 'booking_cancel',
            'reason' => 'Need date change',
        ])->assertRedirect();

        $this->assertDatabaseHas('booking_cancellation_requests', [
            'booking_id' => $booking->id,
            'request_source' => 'customer',
        ]);
    }

    public function test_guest_with_valid_token_can_request_cancellation(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->seededUsers();
        $booking = $this->makeBooking($admin->current_agency_id, BookingStatus::Pending);
        $tokenRaw = 'guest-cancel-token';
        GuestBookingAccessToken::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'token_hash' => hash('sha256', $tokenRaw),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->post(route('guest.bookings.cancellations.store', ['booking' => $booking, 'token' => $tokenRaw]), [
            'cancellation_type' => 'booking_cancel',
            'reason' => 'Guest request',
        ])->assertRedirect();

        $this->assertDatabaseHas('booking_cancellation_requests', [
            'booking_id' => $booking->id,
            'request_source' => 'guest',
        ]);
    }

    public function test_cross_agency_cancellation_and_refund_are_denied(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->seededUsers();
        $otherAgency = Agency::factory()->create();
        $foreignBooking = $this->makeBooking($otherAgency->id, BookingStatus::Confirmed);

        $this->actingAs($admin)->post(route('admin.bookings.cancellations.store', $foreignBooking), [
            'cancellation_type' => 'booking_cancel',
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('admin.bookings.refunds.store', $foreignBooking), [
            'amount' => 1000,
            'method' => 'cash',
        ])->assertForbidden();
    }

    public function test_ticketed_booking_cancellation_process_keeps_status_and_sets_manual_warning(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->seededUsers();
        $booking = $this->makeBooking($admin->current_agency_id, BookingStatus::Ticketed);
        $request = BookingCancellationRequest::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'requested_by' => $admin->id,
            'request_source' => 'admin',
            'status' => 'approved',
            'cancellation_type' => 'ticket_refund',
        ]);

        $this->actingAs($admin)->patch(route('admin.bookings.cancellations.process', $request))->assertRedirect();

        $booking->refresh();
        $request->refresh();
        $this->assertSame('ticketed', $booking->status->value);
        $this->assertSame('processed', $booking->cancellation_status);
        $this->assertNotEmpty($request->meta['manual_warning'] ?? null);
    }

    public function test_audit_and_communication_logs_created_for_cancellation(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->seededUsers();
        $booking = $this->makeBooking($admin->current_agency_id, BookingStatus::Pending);

        $this->actingAs($admin)->post(route('admin.bookings.cancellations.store', $booking), [
            'cancellation_type' => 'booking_cancel',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.cancellation_requested', 'auditable_id' => $booking->id]);
        $this->assertDatabaseHas('communication_logs', ['event' => 'cancellation_requested', 'booking_id' => $booking->id]);
    }

    public function test_agency_admin_can_create_approve_and_mark_refund_paid(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->seededUsers();
        $booking = $this->makeBooking($admin->current_agency_id, BookingStatus::Cancelled);
        BookingPayment::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'status' => 'verified',
            'method' => 'cash',
            'amount' => 5000,
            'currency' => 'PKR',
        ]);

        $this->actingAs($admin)->post(route('admin.bookings.refunds.store', $booking), [
            'amount' => 2500,
            'method' => 'bank_transfer',
        ])->assertRedirect();
        $refund = BookingRefund::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.bookings.refunds.approve', $refund))->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.bookings.refunds.mark-paid', $refund), [
            'reference' => 'REF-001',
        ])->assertRedirect();

        $this->assertSame('paid', $refund->fresh()->status->value);
        $this->assertSame('partial', $booking->fresh()->refund_status);
    }

    public function test_rejected_refund_does_not_count_as_refunded_and_report_page_loads_new_metrics(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        [$admin] = $this->seededUsers();
        $booking = $this->makeBooking($admin->current_agency_id, BookingStatus::Cancelled);

        $this->actingAs($admin)->post(route('admin.bookings.refunds.store', $booking), [
            'amount' => 2000,
            'method' => 'cash',
        ])->assertRedirect();
        $refund = BookingRefund::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.bookings.refunds.reject', $refund), [
            'reason' => 'Invalid request',
        ])->assertRedirect();

        $this->assertSame('rejected', $refund->fresh()->status->value);
        $this->assertNotSame('refunded', $booking->fresh()->refund_status);
        $this->actingAs($admin)->get(route('admin.reports'))->assertOk()->assertSee('Cancellations')->assertSee('Pending refunds');
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: User}
     */
    protected function seededUsers(): array
    {
        $this->seed(OtaFoundationSeeder::class);

        $admin = User::query()->where('email', 'admin@aurora-sky-travel.demo')->firstOrFail();
        $customer = User::query()->firstOrCreate(
            ['email' => 'customer@aurora-sky-travel.demo'],
            [
                'name' => 'Aurora Customer',
                'password' => bcrypt('password'),
                'account_type' => AccountType::Customer,
                'status' => UserAccountStatus::Active,
                'current_agency_id' => $admin->current_agency_id,
            ]
        );

        return [
            $admin,
            User::query()->where('email', 'staff@aurora-sky-travel.demo')->firstOrFail(),
            User::query()->where('email', 'agent@aurora-sky-travel.demo')->firstOrFail(),
            $customer,
        ];
    }

    protected function makeBooking(?int $agencyId, BookingStatus $status, ?int $customerId = null, ?int $agentId = null): Booking
    {
        return Booking::factory()->create([
            'agency_id' => $agencyId,
            'customer_id' => $customerId,
            'agent_id' => $agentId,
            'status' => $status,
            'payment_status' => 'unpaid',
        ]);
    }
}
