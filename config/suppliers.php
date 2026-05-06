<?php

return [
    'sabre' => [
        'default_base_url' => env('SABRE_BASE_URL', 'https://api-crt.cert.havail.sabre.com'),
        'token_path' => env('SABRE_TOKEN_PATH', '/v2/auth/token'),
        'search_path' => env('SABRE_SEARCH_PATH', '/v5/offers/shop'),
        'timeout_seconds' => (int) env('SABRE_TIMEOUT_SECONDS', 30),
        'connect_timeout_seconds' => (int) env('SABRE_CONNECT_TIMEOUT_SECONDS', 10),
    ],
    'duffel' => [
        'default_base_url' => env('DUFFEL_DEFAULT_BASE_URL', 'https://api.duffel.com'),
        'offer_requests_path' => '/air/offer_requests',
        'offer_request_show_path' => '/air/offer_requests/{id}',
        'offers_path' => '/air/offers',
        'offer_show_path' => '/air/offers/{id}',
        'orders_path' => '/air/orders',
        'order_show_path' => '/air/orders/{id}',
        'api_version_header' => 'Duffel-Version',
        'api_version' => env('DUFFEL_API_VERSION', 'v2'),
        'timeout_seconds' => 30,
        'connect_timeout_seconds' => 10,
    ],
];
