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
            $provider = (string) $this->input('provider');
            $credentials = $this->input('credentials', []);
            if (! is_array($credentials)) {
                $credentials = [];
            }
            $filled = array_filter($credentials, fn ($value): bool => trim((string) $value) !== '');
            $keys = array_map('strtolower', array_keys($filled));
            $providerFields = (array) config('supplier_credentials.providers.'.$provider.'.fields', []);

            foreach ($providerFields as $fieldKey => $meta) {
                $required = (bool) ($meta['required'] ?? false);
                if (! $required) {
                    continue;
                }
                if (! in_array(strtolower($fieldKey), $keys, true)) {
                    $validator->errors()->add('credentials.'.$fieldKey, 'This field is required for '.strtoupper($provider).'.');
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
        });
    }
}
