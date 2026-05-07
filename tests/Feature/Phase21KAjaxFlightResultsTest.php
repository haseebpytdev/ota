<?php

namespace Tests\Feature;

use App\Services\FlightSearch\FlightSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class Phase21KAjaxFlightResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_results_page_renders_without_embedded_full_offer_array(): void
    {
        $this->mockFlightSearch(30, ['Duffel partner unavailable']);

        $response = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')
            ->assertOk()
            ->assertSee('data-results-root', false)
            ->assertSee('Load more', false)
            ->assertSee('Provider search is temporarily unavailable.', false);

        $response->assertDontSee('offer-29', false);
    }

    public function test_results_data_endpoint_returns_paginated_slice_and_has_more(): void
    {
        $this->mockFlightSearch(30);
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';
        $this->assertNotSame('', $searchId);

        $response = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12')
            ->assertOk()
            ->assertJsonPath('search_id', $searchId)
            ->assertJsonPath('page', 1)
            ->assertJsonPath('per_page', 12)
            ->assertJsonPath('has_more', true)
            ->assertJsonStructure(['filters' => ['airlines', 'stops', 'refundable', 'price_range', 'cabin_classes', 'baggage_options', 'departure_time_windows', 'arrival_time_windows', 'duration_range', 'duration_buckets', 'layover_airports', 'fare_families', 'bookable_status']]);

        $offers = $response->json('offers');
        $this->assertCount(12, $offers);
    }

    public function test_results_data_endpoint_caps_per_page_at_twenty_five(): void
    {
        $this->mockFlightSearch(40);
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';

        $response = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=999')->assertOk();
        $this->assertSame(25, $response->json('per_page'));
        $this->assertCount(25, $response->json('offers'));
    }

    public function test_results_data_returns_next_page_items(): void
    {
        $this->mockFlightSearch(26);
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';

        $first = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12')->assertOk();
        $second = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=2&per_page=12')->assertOk();

        $this->assertNotSame($first->json('offers.0.offer_id'), $second->json('offers.0.offer_id'));
        $this->assertTrue($second->json('has_more'));
    }

    public function test_expired_search_id_returns_safe_message(): void
    {
        $this->getJson('/flights/results/data?search_id=does-not-exist&page=1')
            ->assertStatus(410)
            ->assertJsonPath('message', 'This fare search has expired. Please search again.');
    }

    public function test_results_payload_contains_pricing_fields_and_excludes_raw_payload(): void
    {
        $this->mockFlightSearch(1);
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';

        $response = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12')->assertOk();
        $offer = $response->json('offers.0');
        $this->assertArrayHasKey('supplier_total', $offer);
        $this->assertArrayHasKey('markup', $offer);
        $this->assertArrayHasKey('service_fee', $offer);
        $this->assertArrayHasKey('final_customer_price', $offer);
        $this->assertEquals($offer['final_customer_price'], $this->toFloat($offer['final_customer_price']));
        $this->assertArrayNotHasKey('raw_payload', $offer);
    }

    public function test_duffel_only_results_keep_provider_and_no_mock_mixing(): void
    {
        $this->mockFlightSearch(6, [], 'duffel');
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';

        $response = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12')->assertOk();
        foreach ($response->json('offers') as $offer) {
            $this->assertSame('duffel', $offer['provider']);
        }
    }

    public function test_selecting_offer_from_ajax_results_reaches_booking_screen(): void
    {
        $this->mockFlightSearch(4, [], 'duffel');
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';
        $json = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12')->assertOk()->json();
        $first = $json['offers'][0];

        $this->get($first['select_url'])
            ->assertOk()
            ->assertSee('Checkout', false)
            ->assertSee('Selected flight', false);
    }

    public function test_non_pkr_offer_uses_currency_code_and_flags_conversion_missing(): void
    {
        $this->mockFlightSearch(1, [], 'duffel', 'USD', 'conversion_missing');
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';
        $offer = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12')
            ->assertOk()
            ->json('offers.0');

        $this->assertSame('conversion_missing', $offer['conversion_status']);
        $this->assertSame('USD', $offer['pricing_currency']);
        $this->assertSame('PKR fare unavailable', (string) $offer['price_display']);
        $this->assertFalse((bool) $offer['can_book']);
        $this->assertNull($offer['select_url']);
    }

    public function test_pkr_offer_uses_rs_display_and_same_currency_status(): void
    {
        $this->mockFlightSearch(1, [], 'duffel', 'PKR', 'same_currency');
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';
        $offer = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12')
            ->assertOk()
            ->json('offers.0');

        $this->assertSame('same_currency', $offer['conversion_status']);
        $this->assertSame('PKR', $offer['pricing_currency']);
        $this->assertStringStartsWith('Rs ', (string) $offer['price_display']);
        $this->assertTrue((bool) $offer['can_book']);
        $this->assertIsString($offer['select_url']);
    }

    public function test_airline_filter_returns_only_selected_airline(): void
    {
        $this->mockFlightSearch(8);
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';
        $response = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12&airline=TB')->assertOk();
        foreach ($response->json('offers') as $offer) {
            $this->assertSame('TB', $offer['airline_code']);
        }
    }

    public function test_stops_and_refundable_filters_work_on_cached_results(): void
    {
        $this->mockFlightSearch(10);
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';

        $response = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12&stops=direct&refundable=1')->assertOk();
        foreach ($response->json('offers') as $offer) {
            $this->assertSame(0, $offer['stops']);
            $this->assertTrue((bool) $offer['refundable']);
        }
    }

    public function test_results_page_contains_collapsible_details_toggle_markup(): void
    {
        $this->mockFlightSearch(3);
        $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')
            ->assertOk()
            ->assertSee('data-toggle-details', false)
            ->assertSee('ota-btn-details', false)
            ->assertSee('Flight details', false);
    }

    public function test_cabin_and_baggage_filter_metadata_is_generated_from_results(): void
    {
        $this->mockFlightSearch(8);
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';
        $filters = $this->getJson('/flights/results/data?search_id='.$searchId.'&page=1&per_page=12')->assertOk()->json('filters');
        $this->assertNotEmpty($filters['cabin_classes']);
        $this->assertNotEmpty($filters['baggage_options']);
    }

    public function test_duration_bucket_and_bookable_only_filters_work(): void
    {
        $this->mockFlightSearch(10, [], 'duffel', 'PKR', 'same_currency');
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';
        $response = $this->getJson('/flights/results/data?search_id='.$searchId.'&duration_bucket=under_6h&bookable_only=1')->assertOk();
        foreach ($response->json('offers') as $offer) {
            $this->assertTrue((bool) $offer['can_book']);
        }
    }

    public function test_no_raw_payload_is_returned_after_filtering(): void
    {
        $this->mockFlightSearch(6);
        $page = $this->get('/flights/results?from=LHE&to=DXB&depart=2026-06-25&trip_type=one_way&cabin=economy&adults=1&children=0&infants=0')->assertOk();
        preg_match('/data-search-id="([^"]+)"/', $page->getContent(), $matches);
        $searchId = $matches[1] ?? '';
        $offers = $this->getJson('/flights/results/data?search_id='.$searchId.'&airline=TA&stops=direct')->assertOk()->json('offers');
        foreach ($offers as $offer) {
            $this->assertArrayNotHasKey('raw_payload', $offer);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fakeOffers(int $count, string $provider = 'mock', string $pricingCurrency = 'PKR', string $conversionStatus = 'same_currency'): array
    {
        $offers = [];
        for ($i = 1; $i <= $count; $i++) {
            $offers[] = [
                'id' => 'offer-'.$i,
                'offer_id' => 'offer-'.$i,
                'supplier_provider' => $provider,
                'supplier_connection_id' => 1,
                'airline_code' => $i % 2 === 0 ? 'TA' : 'TB',
                'airline_name' => $i % 2 === 0 ? 'TestAir' : 'BetaAir',
                'depart_at' => '2026-06-25T0'.($i % 9).':00:00Z',
                'arrive_at' => '2026-06-25T1'.($i % 9).':30:00Z',
                'duration_h' => 2,
                'duration_m' => 30,
                'stops' => $i % 3 === 0 ? 1 : 0,
                'baggage' => $i % 4 === 0 ? '7kg' : '20kg',
                'refundable' => ($i % 2) === 0,
                'cabin' => $i % 2 === 0 ? 'economy' : 'business',
                'fare_family' => $i % 2 === 0 ? 'economy_flex' : 'business_plus',
                'currency' => $pricingCurrency,
                'pricing_currency' => $pricingCurrency,
                'supplier_currency' => $pricingCurrency,
                'conversion_status' => $conversionStatus,
                'base_fare' => 100000 + $i,
                'taxes' => 10000,
                'markup' => 2500,
                'service_fee' => 2499,
                'total' => 114999 + $i,
                'final_customer_price' => 114999 + $i,
                'raw_payload' => ['token' => 'never-expose'],
                'segments' => [
                    [
                        'origin' => 'LHE',
                        'destination' => 'DOH',
                        'departure_at' => '2026-06-25T08:00:00Z',
                        'arrival_at' => '2026-06-25T09:30:00Z',
                        'airline_code' => 'TA',
                        'flight_number' => '123',
                    ],
                    [
                        'origin' => 'DOH',
                        'destination' => 'DXB',
                        'departure_at' => '2026-06-25T10:15:00Z',
                        'arrival_at' => '2026-06-25T12:30:00Z',
                        'airline_code' => 'TA',
                        'flight_number' => '456',
                    ],
                ],
            ];
        }

        return $offers;
    }

    private function mockFlightSearch(int $count, array $warnings = [], string $provider = 'mock', string $pricingCurrency = 'PKR', string $conversionStatus = 'same_currency'): void
    {
        $offers = $this->fakeOffers($count, $provider, $pricingCurrency, $conversionStatus);
        $mock = Mockery::mock(FlightSearchService::class);
        $mock->shouldReceive('searchWithMeta')
            ->andReturn([
                'offers' => $offers,
                'warnings' => $warnings,
            ]);
        $mock->shouldReceive('search')->andReturn($offers);
        $this->instance(FlightSearchService::class, $mock);
    }

    private function toFloat(mixed $value): float
    {
        return (float) $value;
    }
}
