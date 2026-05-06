<?php

return [
    'providers' => [
        'duffel' => [
            'fields' => [
                'access_token' => [
                    'label' => 'Access Token',
                    'type' => 'password',
                    'required' => true,
                    'placeholder' => 'duffel_test_...',
                    'help' => 'Paste your Duffel test access token. Keep environment as sandbox/test.',
                ],
                'api_version' => [
                    'label' => 'API Version',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'v2',
                    'default' => 'v2',
                ],
            ],
        ],
        'sabre' => [
            'fields' => [
                'client_id' => ['label' => 'Client ID', 'type' => 'text', 'required' => true],
                'client_secret' => ['label' => 'Client Secret', 'type' => 'password', 'required' => true],
                'username' => ['label' => 'Username', 'type' => 'text', 'required' => false],
                'password' => ['label' => 'Password', 'type' => 'password', 'required' => false],
                'token' => ['label' => 'Token', 'type' => 'password', 'required' => false],
            ],
        ],
        'pia' => [
            'fields' => [
                'api_key' => ['label' => 'API Key', 'type' => 'password', 'required' => false],
                'client_id' => ['label' => 'Client ID', 'type' => 'text', 'required' => false],
                'client_secret' => ['label' => 'Client Secret', 'type' => 'password', 'required' => false],
            ],
        ],
        'airline_direct' => [
            'fields' => [
                'api_key' => ['label' => 'API Key', 'type' => 'password', 'required' => false],
                'token' => ['label' => 'Token', 'type' => 'password', 'required' => false],
                'username' => ['label' => 'Username', 'type' => 'text', 'required' => false],
                'password' => ['label' => 'Password', 'type' => 'password', 'required' => false],
            ],
        ],
        'amadeus' => [
            'fields' => [
                'client_id' => ['label' => 'Client ID', 'type' => 'text', 'required' => true],
                'client_secret' => ['label' => 'Client Secret', 'type' => 'password', 'required' => true],
            ],
        ],
        'travelport' => [
            'fields' => [
                'client_id' => ['label' => 'Client ID', 'type' => 'text', 'required' => true],
                'client_secret' => ['label' => 'Client Secret', 'type' => 'password', 'required' => true],
            ],
        ],
        'mock' => [
            'fields' => [],
        ],
    ],
];
