<?php

/**
 * Demo-only supplier catalogue for API Settings UI (no vault, no outbound calls).
 */
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
                'WS security certificates (if applicable)',
                'Fare shopping / BFM contract identifiers',
            ],
            'notes' => 'Production Sabre access requires signed commercial agreements, certification, and hardened credential storage. This demo shows the checklist only.',
        ],
        'pia' => [
            'name' => 'PIA',
            'type' => 'Airline direct (Pakistan International Airlines)',
            'status' => 'demo',
            'environment' => 'demo',
            'required_credentials' => [
                'Agency / IATA accreditation numbers',
                'Airline API key or NDC party ID (when offered)',
                'Settlement / BSP linkage for ticketing',
                'Branded fare and bundle codes',
            ],
            'notes' => 'Airline-direct integrations vary by channel (NDC vs traditional). PIA-specific documentation must be reviewed before any live traffic.',
        ],
        'airline_direct' => [
            'name' => 'Airline Direct API',
            'type' => 'Generic NDC / proprietary airline API',
            'status' => 'not_configured',
            'environment' => 'live',
            'required_credentials' => [
                'OAuth client credentials or API token',
                'Office / agency identifiers per carrier',
                'Webhook signing secret (if async offers)',
                'Sandbox vs production base URLs',
            ],
            'notes' => 'Each carrier publishes different schemas. Map once per airline family; do not assume one payload fits all.',
        ],
        'mock' => [
            'name' => 'Mock Supplier',
            'type' => 'In-app demo inventory',
            'status' => 'connected',
            'environment' => 'demo',
            'required_credentials' => [
                'None — mock data is bundled with the white-label demo',
            ],
            'notes' => 'Used for storefront demos, QA, and training. Disable or hide in production tenants where only certified suppliers are allowed.',
        ],
    ],

    'integration_notice' => 'Real API integration begins only after security review of credentials, DPA / BAA where required, and acceptance of each supplier’s technical documentation. Nothing on this screen persists secrets in the demo build.',
];
