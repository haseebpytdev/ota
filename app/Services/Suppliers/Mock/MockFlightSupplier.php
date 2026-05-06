<?php

namespace App\Services\Suppliers\Mock;

class MockFlightSupplier
{
    /**
     * @param  array{origin: string, destination: string, depart_date: string}  $criteria
     * @return list<array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        $origin = strtoupper($criteria['origin']);
        $destination = strtoupper($criteria['destination']);
        $date = $criteria['depart_date'];

        return [
            [
                'id' => 'mock-1',
                'origin' => $origin,
                'destination' => $destination,
                'depart_at' => $date.'T08:30:00',
                'arrive_at' => $date.'T20:15:00',
                'carrier_code' => 'PK',
                'flight_number' => 'PK701',
                'cabin' => 'economy',
                'duration_minutes' => 705,
                'base_fare' => 168000.00,
                'currency' => 'PKR',
            ],
            [
                'id' => 'mock-2',
                'origin' => $origin,
                'destination' => $destination,
                'depart_at' => $date.'T14:05:00',
                'arrive_at' => $date.'T23:40:00',
                'carrier_code' => 'EK',
                'flight_number' => 'EK624',
                'cabin' => 'economy',
                'duration_minutes' => 575,
                'base_fare' => 152500.00,
                'currency' => 'PKR',
            ],
            [
                'id' => 'mock-3',
                'origin' => $origin,
                'destination' => $destination,
                'depart_at' => $date.'T22:50:00',
                'arrive_at' => $date.'T10:10:00',
                'carrier_code' => 'SV',
                'flight_number' => 'SV896',
                'cabin' => 'premium_economy',
                'duration_minutes' => 680,
                'base_fare' => 218900.00,
                'currency' => 'PKR',
            ],
        ];
    }
}
