<?php

/**
 * Admin dashboard “command center” demo tiles (no DB).
 */
return [
    'today_operations' => [
        ['key' => 'pending', 'title' => 'Pending bookings', 'count' => 23, 'hint' => 'Awaiting payment or docs', 'route' => 'admin.bookings'],
        ['key' => 'ticketing', 'title' => 'Ticketing queue', 'count' => 7, 'hint' => 'Schedule changes & reissues', 'route' => 'admin.bookings'],
        ['key' => 'payments', 'title' => 'Payment reminders', 'count' => 12, 'hint' => 'Auto nudges (demo)', 'route' => 'admin.bookings'],
        ['key' => 'agents', 'title' => 'Agent requests', 'count' => 4, 'hint' => 'Credit & profile updates', 'route' => 'admin.agents'],
    ],
    'suppliers' => [
        ['code' => 'SABRE', 'name' => 'Sabre', 'readiness' => 'pending', 'detail' => 'Credentials & PCC not loaded'],
        ['code' => 'PIA', 'name' => 'PIA', 'readiness' => 'pending', 'detail' => 'NDC / BSP path TBD'],
        ['code' => 'NDC', 'name' => 'Airline Direct API', 'readiness' => 'optional', 'detail' => 'Per-carrier keys'],
        ['code' => 'MOCK', 'name' => 'Mock supplier', 'readiness' => 'live', 'detail' => 'Powering this demo'],
    ],
    'revenue_snapshot' => [
        'direct_customer_sales' => 172570000,
        'agent_sales' => 256180000,
        'markup_revenue' => 12840000,
        'period_label' => 'Demo rolling 30d',
    ],
];
