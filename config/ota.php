<?php

return [
    'default_agency_slug' => env('OTA_DEFAULT_AGENCY_SLUG', 'asif-travels'),

    /** Require passport-style fields when origin/destination countries differ (see InternationalRouteDetector). */
    'passport_required_for_international' => filter_var(env('OTA_PASSPORT_REQUIRED_INTERNATIONAL', true), FILTER_VALIDATE_BOOL),

    /** When true, domestic itineraries require national_id_number. Default off for PK domestic flights. */
    'require_domestic_national_id' => filter_var(env('OTA_REQUIRE_DOMESTIC_NATIONAL_ID', false), FILTER_VALIDATE_BOOL),
    'passenger_age_rules' => [
        'adult_min_years' => (int) env('OTA_PASSENGER_ADULT_MIN_YEARS', 12),
        'child_min_years' => (int) env('OTA_PASSENGER_CHILD_MIN_YEARS', 2),
        'child_max_years' => (int) env('OTA_PASSENGER_CHILD_MAX_YEARS', 11),
        'infant_max_years' => (int) env('OTA_PASSENGER_INFANT_MAX_YEARS', 1),
    ],
    'guest_lookup_token_minutes' => (int) env('OTA_GUEST_LOOKUP_TOKEN_MINUTES', 30),
    'private_documents_directory' => env('OTA_PRIVATE_DOCUMENTS_DIRECTORY', 'app/private'),
    'pdf_temp_directory' => env('OTA_PDF_TEMP_DIRECTORY', 'app/private/tmp/pdf'),
    'supplier_default_provider' => env('OTA_SUPPLIER_DEFAULT_PROVIDER', 'duffel'),
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

    /*
    | local/testing only (see BookingController + FareHoldService): if Duffel single-offer validation
    | returns unavailable for an offer ID that belongs to a cached search created within this many
    | seconds, checkout may continue using cached normalized pricing and defer automated supplier
    | booking to manual review. Staging/production ignore this path.
    */
    'provider_unstable_test_mode_window_seconds' => max(1, (int) env('OTA_PROVIDER_UNSTABLE_WINDOW_SECONDS', 120)),

    /**
     * When true (local only), allow the provider-unstable cached-pricing checkout fallback.
     * Testing always allows; staging/production never do (see BookingController).
     */
    'allow_provider_unstable_local' => filter_var(env('OTA_ALLOW_PROVIDER_UNSTABLE_LOCAL', false), FILTER_VALIDATE_BOOL),
];
