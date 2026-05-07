<?php

return [
    'default_agency_slug' => env('OTA_DEFAULT_AGENCY_SLUG', 'asif-travels'),

    /**
     * When true (only honored in local/testing — see App\Support\OtaE2e), flight search uses the mock supplier
     * adapter only so Playwright can complete checkout → review → confirmation without live API drift.
     */
    'e2e_force_mock_supplier' => filter_var(env('E2E_FORCE_MOCK_SUPPLIER', false), FILTER_VALIDATE_BOOL),

    /** Require passport-style fields when origin/destination countries differ (see InternationalRouteDetector). */
    'passport_required_for_international' => filter_var(env('OTA_PASSPORT_REQUIRED_INTERNATIONAL', true), FILTER_VALIDATE_BOOL),

    /** When true, domestic itineraries require national_id_number. Default off for PK domestic flights. */
    'require_domestic_national_id' => filter_var(env('OTA_REQUIRE_DOMESTIC_NATIONAL_ID', false), FILTER_VALIDATE_BOOL),
    'guest_lookup_token_minutes' => (int) env('OTA_GUEST_LOOKUP_TOKEN_MINUTES', 30),
    'private_documents_directory' => env('OTA_PRIVATE_DOCUMENTS_DIRECTORY', 'app/private'),
    'pdf_temp_directory' => env('OTA_PDF_TEMP_DIRECTORY', 'app/private/tmp/pdf'),
    'supplier_default_provider' => env('OTA_SUPPLIER_DEFAULT_PROVIDER', 'mock'),
    'supplier_timeout_seconds' => (int) env('OTA_SUPPLIER_TIMEOUT_SECONDS', 20),

    /*
    | When no uploaded logo exists (airlines.logo_path + public disk file), flight results can
    | still show a logo using a public CDN template. {CODE} = 2-letter IATA (e.g. EK).
    | Set OTA_AIRLINE_LOGO_CDN_ENABLED=false to disable (e.g. offline demos).
    */
    'airline_logo_cdn_enabled' => filter_var(env('OTA_AIRLINE_LOGO_CDN_ENABLED', true), FILTER_VALIDATE_BOOL),
    'airline_logo_cdn_template' => env(
        'OTA_AIRLINE_LOGO_CDN_TEMPLATE',
        'https://images.kiwi.com/airlines/64x64/{CODE}.png'
    ),

    'backup' => [
        'disk' => env('OTA_BACKUP_DISK', 'local'),
        'path' => env('OTA_BACKUP_PATH', 'backups'),
    ],
];
