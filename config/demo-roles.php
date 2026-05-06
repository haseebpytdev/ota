<?php

/**
 * Demo-only role catalogue for white-label OTA (no DB, no enforced auth).
 * Replace with Spatie permissions / policies / tenant RBAC in production.
 */
return [
    'admin' => [
        'label' => 'Administrator',
        'description' => 'Tenant owner or super-user who configures branding, suppliers, markups, and all operational areas.',
        'permissions' => [
            'Full access to admin console',
            'Manage users, agents, and staff accounts',
            'Configure API keys and supplier credentials',
            'Edit markups, fees, and commercial rules',
            'View all bookings and financial reports',
            'Assign roles & permissions (when RBAC is enabled)',
        ],
        'dashboard_url' => '/admin',
    ],

    'staff' => [
        'label' => 'Staff',
        'description' => 'Internal operations team handling ticketing, changes, refunds, and customer support queues.',
        'permissions' => [
            'View and update bookings within scope',
            'Issue exchanges and refunds (policy-bound)',
            'Access customer PNR details and notes',
            'Use support tools and canned responses',
            'No access to supplier credential vault',
        ],
        'dashboard_url' => '/staff',
    ],

    'agent' => [
        'label' => 'Travel agent',
        'description' => 'B2B partner selling on behalf of the white-label brand with agency credit and commission tracking.',
        'permissions' => [
            'Search and book on agency terms',
            'View own bookings and statements',
            'Manage sub-users (when enabled)',
            'Request holds and ticketing within credit limit',
            'Cannot change global markups or API settings',
        ],
        'dashboard_url' => '/agent',
    ],

    'customer' => [
        'label' => 'Customer',
        'description' => 'End traveller with a login to manage trips, documents, and self-service where allowed.',
        'permissions' => [
            'View own itineraries and receipts',
            'Pay or complete pending bookings',
            'Request seat/meal changes when airline rules allow',
            'Update profile and saved travellers',
            'No access to agency or admin areas',
        ],
        'dashboard_url' => '/customer',
    ],

    'guest' => [
        'label' => 'Guest',
        'description' => 'Unauthenticated visitor browsing the public storefront and completing guest checkout.',
        'permissions' => [
            'Search flights and view public fares (mock)',
            'Complete guest checkout flow',
            'View marketing and static pages',
            'Cannot access dashboards or saved trips',
        ],
        'dashboard_url' => '/',
    ],
];
