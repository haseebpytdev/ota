<?php

/**
 * Demo-only reporting aggregates (no DB).
 */
return [
    'demo_note' => 'Reports help OTA owners track sales, revenue, agent performance, popular routes, and payment status.',

    'summary' => [
        'gross_sales' => 428750000,
        'net_revenue' => 31240000,
        'total_bookings' => 3842,
        'ticketed_bookings' => 3188,
        'pending_bookings' => 412,
        'cancelled_bookings' => 242,
        'agent_sales' => 256180000,
        'direct_customer_sales' => 172570000,
    ],

    'monthly_sales' => [
        ['month' => '2026-01', 'bookings' => 612, 'gross_sales' => 68500000, 'net_revenue' => 4980000],
        ['month' => '2026-02', 'bookings' => 598, 'gross_sales' => 70200000, 'net_revenue' => 5120000],
        ['month' => '2026-03', 'bookings' => 645, 'gross_sales' => 71800000, 'net_revenue' => 5210000],
        ['month' => '2026-04', 'bookings' => 671, 'gross_sales' => 74100000, 'net_revenue' => 5380000],
        ['month' => '2026-05', 'bookings' => 316, 'gross_sales' => 36150000, 'net_revenue' => 2550000],
    ],

    'top_routes' => [
        ['route' => 'LHE → DXB', 'bookings' => 428, 'sales' => 48200000, 'average_ticket' => 112617],
        ['route' => 'KHI → JED', 'bookings' => 356, 'sales' => 52100000, 'average_ticket' => 146348],
        ['route' => 'ISB → IST', 'bookings' => 298, 'sales' => 38900000, 'average_ticket' => 130537],
        ['route' => 'LHE → KUL', 'bookings' => 241, 'sales' => 26800000, 'average_ticket' => 111203],
        ['route' => 'KHI → DXB', 'bookings' => 215, 'sales' => 22900000, 'average_ticket' => 106512],
    ],

    'top_agents' => [
        ['agent_code' => 'AGT-1002', 'agency_name' => 'Crescent Tours', 'bookings' => 612, 'sales' => 22180000, 'commission' => 498550],
        ['agent_code' => 'AGT-1001', 'agency_name' => 'SkyLink Travels (Pvt) Ltd', 'bookings' => 428, 'sales' => 18450000, 'commission' => 415125],
        ['agent_code' => 'AGT-1004', 'agency_name' => 'Capital Fly Consultants', 'bookings' => 201, 'sales' => 9650000, 'commission' => 168875],
        ['agent_code' => 'AGT-1006', 'agency_name' => 'Blue Horizon Agencies', 'bookings' => 156, 'sales' => 7420000, 'commission' => 166950],
    ],

    'payment_breakdown' => [
        ['status' => 'Paid', 'count' => 2892, 'amount' => 318400000],
        ['status' => 'Partial', 'count' => 186, 'amount' => 12400000],
        ['status' => 'Unpaid', 'count' => 264, 'amount' => 28750000],
        ['status' => 'Refunded', 'count' => 98, 'amount' => 69100000],
    ],
];
