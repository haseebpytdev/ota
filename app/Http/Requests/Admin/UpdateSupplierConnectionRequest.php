<?php

namespace App\Http\Requests\Admin;

use App\Enums\SupplierProvider;

class UpdateSupplierConnectionRequest extends StoreSupplierConnectionRequest
{
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $provider = (string) $this->input('provider');
            $providerFields = (array) config('supplier_credentials.providers.'.$provider.'.fields', []);
            $credentials = $this->input('credentials', []);
            if (! is_array($credentials)) {
                $credentials = [];
            }

            $filled = array_filter($credentials, fn ($value): bool => trim((string) $value) !== '');
            $keys = array_map('strtolower', array_keys($filled));

            if ($provider === SupplierProvider::Duffel->value) {
                $connection = $this->route('supplierConnection');
                $existingToken = null;
                if ($connection !== null && method_exists($connection, 'getAttribute')) {
                    $currentCreds = $connection->credentials;
                    if (is_array($currentCreds)) {
                        $existingToken = trim((string) ($currentCreds['access_token'] ?? ''));
                    }
                }
                $incomingToken = trim((string) ($credentials['access_token'] ?? ''));
                if ($incomingToken === '' && $existingToken === '') {
                    $validator->errors()->add('credentials.access_token', 'Duffel requires access_token.');
                }

                return;
            }

            if ($filled === []) {
                return;
            }

            if ($provider === SupplierProvider::Pia->value) {
                $hasApiKey = in_array('api_key', $keys, true);
                $hasPair = in_array('client_id', $keys, true) && in_array('client_secret', $keys, true);
                if (! $hasApiKey && ! $hasPair) {
                    $validator->errors()->add('credentials', 'PIA usually requires api_key or client_id/client_secret.');
                }
            }

            if ($provider === SupplierProvider::AirlineDirect->value) {
                $hasApiKey = in_array('api_key', $keys, true);
                $hasToken = in_array('token', $keys, true);
                $hasUserPass = in_array('username', $keys, true) && in_array('password', $keys, true);
                if (! $hasApiKey && ! $hasToken && ! $hasUserPass) {
                    $validator->errors()->add('credentials', 'Airline direct usually needs api_key, token, or username/password.');
                }
            }
            foreach ($providerFields as $fieldKey => $meta) {
                $required = (bool) ($meta['required'] ?? false);
                if (! $required) {
                    continue;
                }
                if (! in_array(strtolower($fieldKey), $keys, true)) {
                    $validator->errors()->add('credentials.'.$fieldKey, 'This field is required when updating this provider.');
                }
            }
        });
    }
}
