<?php

return [
    'default_agency_slug' => env('OTA_DEFAULT_AGENCY_SLUG', 'asif-travels'),
    'guest_lookup_token_minutes' => (int) env('OTA_GUEST_LOOKUP_TOKEN_MINUTES', 30),
    'private_documents_directory' => env('OTA_PRIVATE_DOCUMENTS_DIRECTORY', 'app/private'),
    'pdf_temp_directory' => env('OTA_PDF_TEMP_DIRECTORY', 'app/private/tmp/pdf'),
    'supplier_default_provider' => env('OTA_SUPPLIER_DEFAULT_PROVIDER', 'mock'),
    'supplier_timeout_seconds' => (int) env('OTA_SUPPLIER_TIMEOUT_SECONDS', 20),
    'backup' => [
        'disk' => env('OTA_BACKUP_DISK', 'local'),
        'path' => env('OTA_BACKUP_PATH', 'backups'),
    ],
];
