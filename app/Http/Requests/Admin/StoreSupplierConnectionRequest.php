<?php

namespace App\Http\Requests\Admin;

use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierEnvironment;
use App\Enums\SupplierProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(SupplierProvider::class)],
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['required', Rule::enum(SupplierEnvironment::class)],
            'status' => ['nullable', Rule::enum(SupplierConnectionStatus::class)],
            'base_url' => ['nullable', 'url', 'max:500'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2000'],
            'settings_json' => ['nullable', 'json'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $provider = $this->input('provider');
            $credentials = $this->input('credentials', []);
            if (! is_array($credentials)) {
                $credentials = [];
            }
            $keys = array_map('strtolower', array_keys(array_filter($credentials, fn ($value): bool => trim((string) $value) !== '')));

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
