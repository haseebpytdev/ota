<?php

namespace Tests\Feature;

use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\SupplierConnection;
use App\Services\FlightSearch\FlightSearchService;
use App\Support\OtaE2e;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtaE2eFlightSearchMockOnlyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function when_e2e_mock_flag_enabled_only_mock_offers_are_returned(): void
    {
        config(['ota.e2e_force_mock_supplier' => true]);
        $this->assertTrue(OtaE2e::shouldForceMockSupplier());

        $agency = Agency::factory()->create(['slug' => 'asif-travels-e2e']);
        config(['ota.default_agency_slug' => 'asif-travels-e2e']);

        SupplierConnection::factory()->for($agency)->create([
            'provider' => SupplierProvider::Mock,
            'is_active' => true,
        ]);
        SupplierConnection::factory()->for($agency)->create([
            'provider' => SupplierProvider::Duffel,
            'is_active' => true,
        ]);

        $criteria = [
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => now()->addMonth()->format('Y-m-d'),
            'trip_type' => 'one_way',
            'cabin' => 'economy',
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
        ];

        $service = app(FlightSearchService::class);
        $result = $service->searchWithMeta($criteria, $agency, 'public_guest');

        $this->assertNotEmpty($result['offers']);
        foreach ($result['offers'] as $offer) {
            $this->assertSame('mock', strtolower((string) ($offer['supplier_provider'] ?? '')));
        }
    }
}
