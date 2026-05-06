<?php

/**
 * Demo-only display overlays for mock flight rows (no API).
 * Keys match MockFlightSupplier offer ids.
 */
return [
    'offers' => [
        'mock-1' => [
            'airline_name' => 'Pakistan International Airlines',
            'airline_code' => 'PK',
            'baggage' => '30 kg checked + 7 kg cabin',
            'refundable' => true,
            'fare_family' => 'Economy Flex',
            'seats_left' => 9,
        ],
        'mock-2' => [
            'airline_name' => 'Emirates',
            'airline_code' => 'EK',
            'baggage' => '25 kg checked + 7 kg cabin',
            'refundable' => false,
            'fare_family' => 'Economy Saver',
            'seats_left' => 4,
        ],
        'mock-3' => [
            'airline_name' => 'Saudia',
            'airline_code' => 'SV',
            'baggage' => '2 pcs (23 kg each)',
            'refundable' => true,
            'fare_family' => 'Premium Economy',
            'seats_left' => 6,
        ],
    ],
];
