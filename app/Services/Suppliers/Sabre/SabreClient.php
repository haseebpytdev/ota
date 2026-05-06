<?php

namespace App\Services\Suppliers\Sabre;

use App\Data\FlightSearchRequestData;
use App\Models\SupplierConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SabreClient
{
    public function __construct(
        protected SabreFlightSearchRequestBuilder $requestBuilder,
    ) {}

    public function getAccessToken(SupplierConnection $connection): string
    {
        $cacheKey = 'sabre:token:connection:'.$connection->id;
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = is_array($connection->credentials) ? $connection->credentials : [];
        $clientId = (string) ($credentials['client_id'] ?? '');
        $clientSecret = (string) ($credentials['client_secret'] ?? '');
        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Sabre credentials are missing.');
        }

        $response = Http::asForm()
            ->timeout((int) config('suppliers.sabre.timeout_seconds', 30))
            ->connectTimeout((int) config('suppliers.sabre.connect_timeout_seconds', 10))
            ->retry(1, 300, fn ($exception): bool => $exception instanceof ConnectionException)
            ->post($this->resolveBaseUrl($connection).config('suppliers.sabre.token_path'), [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Sabre authentication failed.');
        }

        $token = (string) data_get($response->json(), 'access_token', '');
        $expiresIn = (int) data_get($response->json(), 'expires_in', 1800);
        if ($token === '') {
            throw new RuntimeException('Sabre authentication response is malformed.');
        }

        Cache::put($cacheKey, $token, max(60, $expiresIn - 60));

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    public function searchFlights(FlightSearchRequestData $request, SupplierConnection $connection): array
    {
        try {
            $token = $this->getAccessToken($connection);
            $payload = $this->requestBuilder->build($request);

            $response = Http::withToken($token)
                ->timeout((int) config('suppliers.sabre.timeout_seconds', 30))
                ->connectTimeout((int) config('suppliers.sabre.connect_timeout_seconds', 10))
                ->retry(1, 300, fn ($exception): bool => $exception instanceof ConnectionException)
                ->post($this->resolveBaseUrl($connection).config('suppliers.sabre.search_path'), $payload);

            if (! $response->successful()) {
                throw new RuntimeException('Sabre search request failed.');
            }

            $json = $response->json();
            if (! is_array($json)) {
                throw new RuntimeException('Sabre search response is malformed.');
            }

            return $json;
        } catch (RequestException|ConnectionException $exception) {
            throw new RuntimeException('Sabre search is temporarily unavailable.', 0, $exception);
        }
    }

    protected function resolveBaseUrl(SupplierConnection $connection): string
    {
        $baseUrl = rtrim((string) ($connection->base_url ?: config('suppliers.sabre.default_base_url')), '/');

        return $baseUrl;
    }
}
