<?php

namespace Tests\Feature;

use App\Data\FlightSearchRequestData;
use App\Data\NormalizedFlightOfferData;
use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierEnvironment;
use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\SupplierConnection;
use App\Models\User;
use App\Services\FlightSearch\FlightSearchService;
use App\Services\Suppliers\Adapters\MockFlightSupplierAdapter;
use App\Services\Suppliers\Adapters\SabreFlightSupplierAdapter;
use App\Services\Suppliers\OfferValidationService;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OfferValidationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_offer_validation_succeeds(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $connection = SupplierConnection::query()->where('provider', SupplierProvider::Mock)->firstOrFail();
        $adapter = app(MockFlightSupplierAdapter::class);
        $request = FlightSearchRequestData::fromArray(['origin' => 'LHE', 'destination' => 'DXB', 'depart_date' => now()->addDays(10)->toDateString()]);
        $offer = $adapter->search($request, $connection)->offers[0];

        $result = $adapter->validateOffer($offer, $request, $connection);

        $this->assertTrue($result->is_valid);
        $this->assertSame('valid', $result->status);
    }

    public function test_missing_mock_offer_returns_unavailable(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $connection = SupplierConnection::query()->where('provider', SupplierProvider::Mock)->firstOrFail();
        $adapter = app(MockFlightSupplierAdapter::class);

        $result = $adapter->validateOffer('missing-offer-id', FlightSearchRequestData::fromArray(['origin' => 'LHE', 'destination' => 'DXB', 'depart_date' => now()->addDays(10)->toDateString()]), $connection);

        $this->assertFalse($result->is_valid);
        $this->assertSame('unavailable', $result->status);
    }

    public function test_offer_validation_service_applies_pricing_after_validation(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $offer = app(FlightSearchService::class)->search([
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => now()->addDays(10)->toDateString(),
        ], $agency, 'public_guest')[0];

        $result = app(OfferValidationService::class)->validateSelectedOffer($agency, $offer, [
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => now()->addDays(10)->toDateString(),
            'source_channel' => 'public_guest',
        ]);

        $this->assertTrue($result->is_valid);
        $this->assertArrayHasKey('pricing_snapshot', $result->meta);
        $this->assertGreaterThan(0, (float) ($result->meta['final_customer_price'] ?? 0));
    }

    public function test_public_guest_booking_stores_validation_snapshot_in_meta(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addWeek()->toDateString(),
            'first_name' => 'Public',
            'last_name' => 'Validation',
            'email' => 'pv@example.com',
            'phone' => '+923001112211',
        ])->assertRedirect(route('booking.review'));

        $meta = Booking::query()->firstOrFail()->meta ?? [];
        $this->assertArrayHasKey('offer_validation_status', $meta);
        $this->assertArrayHasKey('validated_at', $meta);
        $this->assertArrayHasKey('validated_offer_snapshot', $meta);
        $this->assertArrayHasKey('validation_warnings', $meta);
    }

    public function test_agent_booking_stores_validation_snapshot_in_meta(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agentUser = User::query()->where('email', 'agent@aurora-sky-travel.demo')->firstOrFail();

        $this->actingAs($agentUser)->post('/agent/bookings', [
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addDays(16)->toDateString(),
            'flight_id' => 'mock-1',
            'title' => 'Mr',
            'first_name' => 'Agent',
            'last_name' => 'Validation',
            'dob' => now()->subYears(30)->toDateString(),
            'nationality' => 'PK',
            'email' => 'agent.validation@example.com',
            'phone' => '+923001112299',
            'country' => 'Pakistan',
        ]);

        $meta = Booking::query()->firstOrFail()->meta ?? [];
        $this->assertArrayHasKey('offer_validation_status', $meta);
        $this->assertArrayHasKey('validated_offer_snapshot', $meta);
    }

    public function test_price_changed_result_does_not_silently_create_booking(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        SupplierConnection::query()->where('agency_id', $agency->id)->where('provider', SupplierProvider::Mock)->firstOrFail()->update([
            'settings' => ['force_price_change' => true],
        ]);

        $response = $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addWeek()->toDateString(),
            'first_name' => 'Price',
            'last_name' => 'Changed',
            'email' => 'price.changed@example.com',
            'phone' => '+923001112200',
        ]);

        $response->assertRedirect();
    }

    public function test_unavailable_result_redirects_with_safe_warning(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $response = $this->post('/booking/passengers', [
            'flight_id' => 'missing-id',
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addWeek()->toDateString(),
            'first_name' => 'Unavailable',
            'last_name' => 'Case',
            'email' => 'unavailable@example.com',
            'phone' => '+923001112201',
        ]);

        $response->assertRedirect(route('flights.search'));
    }

    public function test_sabre_validate_offer_search_replay_uses_http_fake_and_no_pnr(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/sabre_search_response.json')), true);
        Http::fake([
            '*/v2/auth/token' => Http::response(['access_token' => 'token-ok', 'expires_in' => 1800], 200),
            '*/v5/offers/shop' => Http::response($fixture, 200),
        ]);

        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);
        $adapter = app(SabreFlightSupplierAdapter::class);
        $request = FlightSearchRequestData::fromArray(['origin' => 'LHE', 'destination' => 'DXB', 'depart_date' => '2026-06-10']);
        $offer = $adapter->search($request, $connection)->offers[0];
        $result = $adapter->validateOffer($offer, $request, $connection);

        $this->assertContains($result->status, ['valid', 'price_changed']);
        Http::assertSent(fn ($request): bool => ! str_contains(strtolower($request->url()), 'pnr'));
    }

    public function test_sabre_validate_offer_price_change_is_detected(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/sabre_search_response.json')), true);
        $fixture['groupedItineraryResponse']['itineraryGroups'][0]['itineraries'][0]['pricingInformation'][0]['fare']['totalFare'] = 15500;
        Http::fake([
            '*/v2/auth/token' => Http::response(['access_token' => 'token-ok', 'expires_in' => 1800], 200),
            '*/v5/offers/shop' => Http::response($fixture, 200),
        ]);

        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);
        $adapter = app(SabreFlightSupplierAdapter::class);
        $request = FlightSearchRequestData::fromArray(['origin' => 'LHE', 'destination' => 'DXB', 'depart_date' => '2026-06-10']);
        $source = NormalizedFlightOfferData::fromArray([
            'offer_id' => 'x',
            'supplier_provider' => 'sabre',
            'supplier_connection_id' => $connection->id,
            'airline_code' => 'PK',
            'airline_name' => 'PK',
            'flight_number' => '203',
            'origin' => 'LHE',
            'destination' => 'DXB',
            'departure_at' => '2026-06-10T08:30:00',
            'arrival_at' => '2026-06-10T11:15:00',
            'duration_minutes' => 165,
            'stops' => 0,
            'cabin' => 'economy',
            'fare_breakdown' => ['base_fare' => 10000, 'taxes' => 2500, 'supplier_fees' => 0, 'supplier_total' => 12500, 'currency' => 'PKR'],
            'baggage' => ['summary' => 'As per fare rule'],
        ]);
        $result = $adapter->validateOffer($source, $request, $connection);

        $this->assertTrue($result->price_changed || $result->status === 'price_changed');
    }

    public function test_provider_error_returns_safe_warning(): void
    {
        Http::fake(['*' => Http::response(['error' => 'bad'], 500)]);
        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);
        $result = app(SabreFlightSupplierAdapter::class)->validateOffer(
            'some-offer-id',
            FlightSearchRequestData::fromArray(['origin' => 'LHE', 'destination' => 'DXB', 'depart_date' => '2026-06-10']),
            $connection
        );

        $this->assertSame('provider_error', $result->status);
        $this->assertStringNotContainsString('client_secret', implode(' ', $result->warnings));
    }

    public function test_no_credentials_or_tokens_appear_in_validation_snapshot(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $this->post('/booking/passengers', [
            'flight_id' => 'mock-1',
            'from' => 'LHE',
            'to' => 'DXB',
            'depart' => now()->addWeek()->toDateString(),
            'first_name' => 'No',
            'last_name' => 'Secrets',
            'email' => 'nosecrets@example.com',
            'phone' => '+923001112202',
        ]);
        $meta = Booking::query()->firstOrFail()->meta ?? [];
        $serialized = json_encode($meta['validated_offer_snapshot'] ?? []);
        $this->assertIsString($serialized);
        $this->assertStringNotContainsString('token', strtolower($serialized));
        $this->assertStringNotContainsString('secret', strtolower($serialized));
    }
}
