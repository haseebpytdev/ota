<?php

namespace App\Http\Requests\Admin;

use App\Enums\SupplierProvider;

class UpdateSupplierConnectionRequest extends StoreSupplierConnectionRequest
{
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $credentials = $this->input('credentials', []);
            if (! is_array($credentials)) {
                $credentials = [];
            }

            $filled = array_filter($credentials, fn ($value): bool => trim((string) $value) !== '');
            if ($filled === []) {
                return;
            }

            $provider = $this->input('provider');
            $keys = array_map('strtolower', array_keys($filled));

            if ($provider === SupplierProvider::Sabre->value) {
                if (! in_array('client_id', $keys, true) || ! in_array('client_secret', $keys, true)) {
                    $validator->errors()->add('credentials', 'Sabre usually requires client_id and client_secret.');
                }
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

            if ($provider === SupplierProvider::Duffel->value && ! in_array('access_token', $keys, true)) {
                $validator->errors()->add('credentials', 'Duffel requires access_token.');
            }
        });
    }
}
