<?php

namespace App\Services\Suppliers\Sabre;

use App\Data\FlightSearchRequestData;

class SabreFlightSearchRequestBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(FlightSearchRequestData $request): array
    {
        return [
            'OriginDestinations' => [
                [
                    'Id' => '1',
                    'OriginLocation' => ['LocationCode' => strtoupper($request->origin)],
                    'DestinationLocation' => ['LocationCode' => strtoupper($request->destination)],
                    'DepartureDateTime' => $request->departure_date.'T00:00:00',
                ],
            ],
            'TravelPreferences' => [
                'CabinPref' => [[
                    'Cabin' => strtoupper($request->cabin),
                    'PreferLevel' => 'Preferred',
                ]],
            ],
            'TravelerInfoSummary' => [
                'AirTravelerAvail' => [[
                    'PassengerTypeQuantity' => $this->passengerTypeQuantities($request),
                ]],
            ],
            'Currency' => strtoupper($request->currency),
            'TPA_Extensions' => [
                'IntelliSellTransaction' => [
                    'RequestType' => ['Name' => '50ITINS'],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, int|string>>
     */
    protected function passengerTypeQuantities(FlightSearchRequestData $request): array
    {
        $quantities = [];

        if ($request->adults > 0) {
            $quantities[] = ['Code' => 'ADT', 'Quantity' => $request->adults];
        }
        if ($request->children > 0) {
            $quantities[] = ['Code' => 'CNN', 'Quantity' => $request->children];
        }
        if ($request->infants > 0) {
            $quantities[] = ['Code' => 'INF', 'Quantity' => $request->infants];
        }
        if ($quantities === []) {
            $quantities[] = ['Code' => 'ADT', 'Quantity' => 1];
        }

        return $quantities;
    }
}
