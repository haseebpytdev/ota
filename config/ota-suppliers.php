<?php

return [
    'suppliers' => [
        'sabre' => [
            'name' => 'Sabre',
            'type' => 'GDS / NDC aggregator',
            'status' => 'not_configured',
            'environment' => 'sandbox',
            'required_credentials' => [
                'SOAP/REST client ID & secret (EPR)',
                'PCC / pseudo city code',
                'IP allow-listing with Sabre',
            ],
            'notes' => 'Production Sabre access requires signed commercial agreements and certification.',
        ],
        'pia' => [
            'name' => 'PIA',
            'type' => 'Airline direct',
            'status' => 'not_configured',
            'environment' => 'sandbox',
            'required_credentials' => [
                'Agency / IATA accreditation numbers',
                'Airline API key or NDC party ID',
            ],
            'notes' => 'Airline-direct integrations vary by channel and require carrier documentation review.',
        ],
        'airline_direct' => [
            'name' => 'Airline Direct API',
            'type' => 'Generic NDC / proprietary airline API',
            'status' => 'not_configured',
            'environment' => 'live',
            'required_credentials' => [
                'OAuth client credentials or API token',
                'Office / agency identifiers per carrier',
            ],
            'notes' => 'Each carrier publishes different schemas and payload constraints.',
        ],
        'mock' => [
            'name' => 'Mock Supplier',
            'type' => 'In-app inventory',
            'status' => 'connected',
            'environment' => 'sandbox',
            'required_credentials' => ['None'],
            'notes' => 'Use only for QA and staging.',
        ],
    ],
    'integration_notice' => 'Real API integration begins only after credential security and technical documentation review.',
];
