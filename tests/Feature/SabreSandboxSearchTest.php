<?php

namespace Tests\Feature;

use App\Data\FlightSearchRequestData;
use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierEnvironment;
use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\SupplierConnection;
use App\Services\FlightSearch\FlightSearchService;
use App\Services\Suppliers\Adapters\SabreFlightSupplierAdapter;
use App\Services\Suppliers\Sabre\SabreClient;
use App\Services\Suppliers\Sabre\SabreFlightSearchNormalizer;
use Database\Seeders\OtaFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SabreSandboxSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_sabre_client_obtains_access_token_and_caches_it(): void
    {
        Http::fake([
            '*' => Http::response(['access_token' => 'token-123', 'expires_in' => 1800], 200),
        ]);
        Cache::flush();

        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);

        $client = app(SabreClient::class);
        $tokenOne = $client->getAccessToken($connection);
        $tokenTwo = $client->getAccessToken($connection);

        $this->assertSame('token-123', $tokenOne);
        $this->assertSame('token-123', $tokenTwo);
        Http::assertSentCount(1);
    }

    public function test_sabre_client_sends_search_request_with_expected_payload_shape(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/sabre_search_response.json')), true);
        Http::fake([
            '*/v2/auth/token' => Http::response(['access_token' => 'token-abc', 'expires_in' => 1800], 200),
            '*/v5/offers/shop' => Http::response($fixture, 200),
        ]);

        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);

        $client = app(SabreClient::class);
        $client->searchFlights(
            FlightSearchRequestData::fromArray([
                'origin' => 'LHE',
                'destination' => 'DXB',
                'depart_date' => '2026-06-10',
                'adults' => 1,
                'children' => 1,
                'currency' => 'PKR',
            ]),
            $connection
        );

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/v5/offers/shop')) {
                return true;
            }

            $payload = $request->data();

            return isset($payload['OriginDestinations'][0]['OriginLocation']['LocationCode'])
                && isset($payload['TravelerInfoSummary']['AirTravelerAvail'][0]['PassengerTypeQuantity']);
        });
    }

    public function test_sabre_adapter_returns_warning_if_credentials_missing(): void
    {
        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => [],
        ]);

        $result = app(SabreFlightSupplierAdapter::class)->search(
            FlightSearchRequestData::fromArray(['origin' => 'LHE', 'destination' => 'DXB', 'depart_date' => now()->addDays(8)->toDateString()]),
            $connection
        );

        $this->assertSame([], $result->offers);
        $this->assertSame(['Sabre credentials are not configured.'], $result->warnings);
    }

    public function test_sabre_adapter_handles_auth_failure_safely(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'wrong'],
            'base_url' => 'https://example.sabre.test',
        ]);

        $result = app(SabreFlightSupplierAdapter::class)->search(
            FlightSearchRequestData::fromArray(['origin' => 'LHE', 'destination' => 'DXB', 'depart_date' => now()->addDays(8)->toDateString()]),
            $connection
        );

        $this->assertSame([], $result->offers);
        $this->assertSame(['Sabre search is temporarily unavailable. Please try again later.'], $result->warnings);
    }

    public function test_sabre_adapter_handles_search_timeout_safely(): void
    {
        Http::fake([
            '*/v2/auth/token' => Http::response(['access_token' => 'token-ok', 'expires_in' => 1800], 200),
            '*/v5/offers/shop' => fn () => throw new ConnectionException('timeout'),
        ]);

        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);

        $result = app(SabreFlightSupplierAdapter::class)->search(
            FlightSearchRequestData::fromArray(['origin' => 'LHE', 'destination' => 'DXB', 'depart_date' => now()->addDays(8)->toDateString()]),
            $connection
        );

        $this->assertSame([], $result->offers);
        $this->assertSame(['Sabre search is temporarily unavailable. Please try again later.'], $result->warnings);
    }

    public function test_sabre_normalizer_converts_fixture_response_into_normalized_offer_data(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/sabre_search_response.json')), true);
        $connection = SupplierConnection::factory()->create([
            'provider' => SupplierProvider::Sabre,
        ]);

        $offers = app(SabreFlightSearchNormalizer::class)->normalize($fixture, $connection);

        $this->assertCount(1, $offers);
        $this->assertSame('sabre', $offers[0]->supplier_provider);
        $this->assertSame('PK', $offers[0]->airline_code);
        $this->assertSame('LHE', $offers[0]->origin);
    }

    public function test_flight_search_service_includes_sabre_offers_when_connection_active(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/sabre_search_response.json')), true);

        $sabreConnection = SupplierConnection::query()->where('agency_id', $agency->id)->where('provider', SupplierProvider::Sabre)->firstOrFail();
        $sabreConnection->update([
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);

        Http::fake([
            '*/v2/auth/token' => Http::response(['access_token' => 'token-ok', 'expires_in' => 1800], 200),
            '*/v5/offers/shop' => Http::response($fixture, 200),
        ]);

        $offers = app(FlightSearchService::class)->search([
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => '2026-06-10',
        ], $agency, 'public_guest');

        $this->assertTrue(collect($offers)->contains(fn (array $offer): bool => $offer['supplier_provider'] === 'sabre'));
    }

    public function test_inactive_sabre_connection_is_skipped(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $sabreConnection = SupplierConnection::query()->where('agency_id', $agency->id)->where('provider', SupplierProvider::Sabre)->firstOrFail();
        $sabreConnection->update([
            'is_active' => false,
            'status' => SupplierConnectionStatus::Inactive,
        ]);

        $result = app(FlightSearchService::class)->searchWithMeta([
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => '2026-06-10',
        ], $agency, 'public_guest');

        $this->assertFalse(collect($result['offers'])->contains(fn (array $offer): bool => $offer['supplier_provider'] === 'sabre'));
    }

    public function test_pricing_rule_service_applies_markup_to_sabre_normalized_offers(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/sabre_search_response.json')), true);

        $sabreConnection = SupplierConnection::query()->where('agency_id', $agency->id)->where('provider', SupplierProvider::Sabre)->firstOrFail();
        $sabreConnection->update([
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);

        Http::fake([
            '*/v2/auth/token' => Http::response(['access_token' => 'token-ok', 'expires_in' => 1800], 200),
            '*/v5/offers/shop' => Http::response($fixture, 200),
        ]);

        $sabreOffer = collect(app(FlightSearchService::class)->search([
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => '2026-06-10',
        ], $agency, 'public_guest'))->firstWhere('supplier_provider', 'sabre');

        $this->assertNotNull($sabreOffer);
        $this->assertGreaterThan((float) $sabreOffer['base_fare'], (float) $sabreOffer['final_customer_price']);
    }

    public function test_public_results_page_can_render_sabre_offers(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/sabre_search_response.json')), true);
        $sabreConnection = SupplierConnection::query()->where('agency_id', $agency->id)->where('provider', SupplierProvider::Sabre)->firstOrFail();
        $sabreConnection->update([
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'xyz'],
            'base_url' => 'https://example.sabre.test',
        ]);

        Http::fake([
            '*/v2/auth/token' => Http::response(['access_token' => 'token-ok', 'expires_in' => 1800], 200),
            '*/v5/offers/shop' => Http::response($fixture, 200),
        ]);

        $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-10')
            ->assertOk()
            ->assertSee('PK');
    }

    public function test_no_credentials_or_tokens_appear_in_normalized_offer_snapshot(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/sabre_search_response.json')), true);
        $sabreConnection = SupplierConnection::query()->where('agency_id', $agency->id)->where('provider', SupplierProvider::Sabre)->firstOrFail();
        $sabreConnection->update([
            'environment' => SupplierEnvironment::Sandbox,
            'status' => SupplierConnectionStatus::Active,
            'is_active' => true,
            'credentials' => ['client_id' => 'abc', 'client_secret' => 'super-secret'],
            'base_url' => 'https://example.sabre.test',
        ]);
        Http::fake([
            '*/v2/auth/token' => Http::response(['access_token' => 'token-secret', 'expires_in' => 1800], 200),
            '*/v5/offers/shop' => Http::response($fixture, 200),
        ]);

        $offer = collect(app(FlightSearchService::class)->search([
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => '2026-06-10',
        ], $agency, 'public_guest'))->firstWhere('supplier_provider', 'sabre');

        $serialized = json_encode($offer);
        $this->assertIsString($serialized);
        $this->assertStringNotContainsString('super-secret', $serialized);
        $this->assertStringNotContainsString('token-secret', $serialized);
        $this->assertStringNotContainsString('client_secret', $serialized);
    }

    public function test_mock_supplier_continues_to_work(): void
    {
        $this->seed(OtaFoundationSeeder::class);
        $agency = Agency::query()->where('slug', 'aurora-sky-travel')->firstOrFail();

        $offers = app(FlightSearchService::class)->search([
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => now()->addDays(10)->toDateString(),
        ], $agency, 'public_guest');

        $this->assertTrue(collect($offers)->contains(fn (array $offer): bool => $offer['supplier_provider'] === 'mock'));
    }
}
