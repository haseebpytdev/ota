<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingDocumentStatus;
use App\Enums\BookingDocumentType;
use App\Enums\BookingStatus;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingDocument;
use App\Models\GuestBookingAccessToken;
use App\Models\User;
use App\Services\Customer\GuestBookingAccessService;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerPortalAndGuestLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_own_booking(): void
    {
        [$customer, $booking] = $this->customerBooking();

        $this->actingAs($customer)->get(route('customer.bookings.show', $booking))->assertOk();
    }

    public function test_customer_cannot_view_another_customer_booking(): void
    {
        [$customer] = $this->customerBooking();
        [, $otherBooking] = $this->customerBooking();

        $this->actingAs($customer)->get(route('customer.bookings.show', $otherBooking))->assertForbidden();
    }

    public function test_customer_can_download_own_booking_document(): void
    {
        [$customer, $booking] = $this->customerBooking();
        $document = $this->documentForBooking($booking);

        $this->actingAs($customer)->get(route('customer.documents.download', $document))->assertOk();
    }

    public function test_customer_cannot_download_another_booking_document(): void
    {
        [$customer] = $this->customerBooking();
        [, $otherBooking] = $this->customerBooking();
        $document = $this->documentForBooking($otherBooking);

        $this->actingAs($customer)->get(route('customer.documents.download', $document))->assertForbidden();
    }

    public function test_customer_can_submit_payment_proof_for_own_booking(): void
    {
        [$customer, $booking] = $this->customerBooking();

        $this->actingAs($customer)->post(route('customer.bookings.payment-proof', $booking), [
            'method' => 'bank_transfer',
            'amount' => 1000,
            'payment_reference' => 'CUST-1',
        ])->assertRedirect();

        $this->assertDatabaseHas('booking_payments', ['booking_id' => $booking->id, 'status' => 'submitted']);
    }

    public function test_guest_lookup_requires_booking_reference_plus_matching_email_or_phone(): void
    {
        [, $booking] = $this->customerBooking();
        $booking->contact()->update(['email' => 'guestmatch@example.test']);

        $this->post(route('lookup-booking.submit'), [
            'booking_reference' => $booking->booking_reference,
            'email' => 'guestmatch@example.test',
        ])->assertRedirectContains('/guest/bookings/'.$booking->id.'/access/');
    }

    public function test_guest_lookup_rejects_reference_only_access(): void
    {
        [, $booking] = $this->customerBooking();

        $this->post(route('lookup-booking.submit'), [
            'booking_reference' => $booking->booking_reference,
        ])->assertSessionHasErrors('lookup');
    }

    public function test_guest_access_token_is_hashed_at_rest(): void
    {
        [, $booking] = $this->customerBooking();
        $raw = app(GuestBookingAccessService::class)->createTokenForBooking($booking, 'a@example.test', null);
        $stored = GuestBookingAccessToken::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();

        $this->assertNotSame($raw, $stored->token_hash);
        $this->assertSame(hash('sha256', $raw), $stored->token_hash);
    }

    public function test_valid_guest_token_allows_booking_view(): void
    {
        [, $booking] = $this->customerBooking();
        $token = app(GuestBookingAccessService::class)->createTokenForBooking($booking, 'a@example.test', null);

        $this->get(route('guest.bookings.show', ['booking' => $booking, 'token' => $token]))->assertOk();
    }

    public function test_expired_guest_token_is_denied(): void
    {
        [, $booking] = $this->customerBooking();
        $token = app(GuestBookingAccessService::class)->createTokenForBooking($booking, 'a@example.test', null);
        GuestBookingAccessToken::query()->where('booking_id', $booking->id)->update(['expires_at' => now()->subMinute()]);

        $this->get(route('guest.bookings.show', ['booking' => $booking, 'token' => $token]))->assertForbidden();
    }

    public function test_guest_can_download_allowed_document_with_valid_token(): void
    {
        [, $booking] = $this->customerBooking();
        $document = $this->documentForBooking($booking);
        $token = app(GuestBookingAccessService::class)->createTokenForBooking($booking, 'a@example.test', null);

        $this->get(route('guest.documents.download', ['bookingDocument' => $document, 'token' => $token]))->assertOk();
    }

    public function test_guest_cannot_access_admin_staff_internal_notes(): void
    {
        [, $booking] = $this->customerBooking();
        $booking->bookingNotes()->create([
            'agency_id' => $booking->agency_id,
            'user_id' => null,
            'note_type' => 'internal',
            'note' => 'Internal Admin Secret Note',
            'is_customer_visible' => false,
        ]);
        $token = app(GuestBookingAccessService::class)->createTokenForBooking($booking, 'a@example.test', null);

        $this->get(route('guest.bookings.show', ['booking' => $booking, 'token' => $token]))
            ->assertOk()
            ->assertDontSee('Internal Admin Secret Note');
    }

    public function test_customer_portal_does_not_expose_audit_logs_or_raw_supplier_payload(): void
    {
        [$customer, $booking] = $this->customerBooking([
            'meta' => ['supplier_payload' => ['token' => 'secret_token_value']],
        ]);

        $this->actingAs($customer)->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('audit_logs')
            ->assertDontSee('secret_token_value');
    }

    public function test_public_lookup_routes_remain_unauthenticated(): void
    {
        $this->get(route('lookup-booking.form'))->assertOk();
        $this->post(route('lookup-booking.submit'), ['booking_reference' => 'ABC'])->assertSessionHasErrors('lookup');
    }

    public function test_customer_routes_remain_authenticated_and_customer_only(): void
    {
        [, $booking] = $this->customerBooking();
        $staff = User::query()->where('email', 'staff@ota.demo')->firstOrFail();

        $this->get(route('customer.bookings.index'))->assertRedirect();
        $this->actingAs($staff)->get(route('customer.bookings.show', $booking))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: Booking}
     */
    protected function customerBooking(array $overrides = []): array
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'asif-travels')->firstOrFail();
        $customer = User::factory()->create([
            'account_type' => AccountType::Customer,
            'current_agency_id' => $agency->id,
        ]);
        $agency->users()->attach($customer->id, ['role' => 'customer']);
        $booking = Booking::factory()->create(array_merge([
            'agency_id' => $agency->id,
            'customer_id' => $customer->id,
            'status' => BookingStatus::PaymentPending,
            'payment_status' => 'unpaid',
            'booking_reference' => 'BKG-'.strtoupper((string) fake()->unique()->numberBetween(1000, 9999)),
            'route' => 'LHE-KHI',
        ], $overrides));
        $booking->contact()->create([
            'email' => 'customer@example.test',
            'phone' => '03001234567',
            'country' => 'PK',
            'address_line' => 'Street 1',
        ]);
        $booking->passengers()->create([
            'passenger_index' => 0,
            'title' => 'Mr',
            'first_name' => 'Ali',
            'last_name' => 'Khan',
        ]);

        return [$customer, $booking->fresh()];
    }

    protected function documentForBooking(Booking $booking): BookingDocument
    {
        $path = 'private/agency-'.$booking->agency_id.'/bookings/'.$booking->id.'/documents/test.pdf';
        Storage::disk('local')->put($path, 'PDF FILE');

        return BookingDocument::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'document_type' => BookingDocumentType::BookingConfirmation,
            'document_number' => 'BC-'.$booking->booking_reference,
            'title' => 'Booking Confirmation',
            'file_path' => $path,
            'status' => BookingDocumentStatus::Generated,
            'generated_by' => $booking->customer_id,
            'generated_at' => now(),
        ]);
    }
}
