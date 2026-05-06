<?php

/**
 * Demo-only commercial rules for Markups admin UI (no persistence, no pricing engine).
 */
return [
    'global_markup' => [
        'name' => 'Platform service fee',
        'rule_type' => 'channel_markup',
        'value' => 2.5,
        'value_type' => 'percentage',
        'applies_to' => 'All published fares before taxes and carrier surcharges',
        'status' => 'active',
    ],

    'route_markups' => [
        [
            'name' => 'Gulf trunk — Lahore / Karachi to UAE',
            'rule_type' => 'origin_destination',
            'value' => 3500,
            'value_type' => 'fixed',
            'applies_to' => 'PK → AE (DXB, AUH, SHJ) economy base',
            'status' => 'active',
        ],
        [
            'name' => 'Saudi Arabia — major hubs',
            'rule_type' => 'origin_destination',
            'value' => 1.75,
            'value_type' => 'percentage',
            'applies_to' => 'PK → SA (JED, RUH, DMM)',
            'status' => 'active',
        ],
        [
            'name' => 'Promotional cap — Islamabad departures',
            'rule_type' => 'promo_cap',
            'value' => 1500,
            'value_type' => 'fixed',
            'applies_to' => 'ISB outbound only, stackable limit per PNR',
            'status' => 'draft',
        ],
    ],

    'airline_markups' => [
        [
            'name' => 'PIA negotiated add-on',
            'rule_type' => 'carrier_code',
            'value' => 1.25,
            'value_type' => 'percentage',
            'applies_to' => 'Carrier PK — published economy',
            'status' => 'active',
        ],
        [
            'name' => 'Emirates distribution fee',
            'rule_type' => 'carrier_code',
            'value' => 4200,
            'value_type' => 'fixed',
            'applies_to' => 'Carrier EK — all cabins',
            'status' => 'active',
        ],
        [
            'name' => 'Saudia NDC channel',
            'rule_type' => 'carrier_code',
            'value' => 0.9,
            'value_type' => 'percentage',
            'applies_to' => 'Carrier SV — NDC-sourced fares only',
            'status' => 'inactive',
        ],
    ],

    'agent_commissions' => [
        [
            'name' => 'Default B2B commission',
            'rule_type' => 'tier_default',
            'value' => 3.0,
            'value_type' => 'percentage',
            'applies_to' => 'All IATA-bonded agents unless overridden',
            'status' => 'active',
        ],
        [
            'name' => 'Gold agency tier',
            'rule_type' => 'tier_volume',
            'value' => 2.25,
            'value_type' => 'percentage',
            'applies_to' => 'Agents above PKR 50M rolling 12-month sales',
            'status' => 'active',
        ],
        [
            'name' => 'Flat incentive — new partner onboarding',
            'rule_type' => 'promo_flat',
            'value' => 500,
            'value_type' => 'fixed',
            'applies_to' => 'First 100 issued tickets per new agency ID (2026 promo)',
            'status' => 'draft',
        ],
    ],

    'demo_note' => 'Final fare shown to the customer can include admin markup, route markup, airline markup, and agent commission rules.',
];
