<?php

namespace App\Services\Suppliers\Duffel;

use App\Data\FlightSearchRequestData;
use App\Models\Booking;
use App\Models\SupplierConnection;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DuffelClient
{
    public function __construct(
        private readonly DuffelOfferRequestBuilder $offerRequestBuilder,
        private readonly DuffelOrderRequestBuilder $orderRequestBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function createOfferRequest(FlightSearchRequestData $request, SupplierConnection $connection): array
    {
        $payload = $this->offerRequestBuilder->build($request);
        $path = $this->path('offer_requests_path');
        $separator = str_contains($path, '?') ? '&' : '?';

        return $this->send(
            $connection,
            'POST',
            $path.$separator.'return_offers=true',
            $payload
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getOfferRequest(string $offerRequestId, SupplierConnection $connection): array
    {
        $path = str_replace('{id}', rawurlencode($offerRequestId), $this->path('offer_request_show_path'));

        return $this->send($connection, 'GET', $path);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function listOffers(array $query, SupplierConnection $connection): array
    {
        return $this->send($connection, 'GET', $this->path('offers_path'), query: $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOffer(string $offerId, SupplierConnection $connection): array
    {
        $path = str_replace('{id}', rawurlencode($offerId), $this->path('offer_show_path'));

        return $this->send($connection, 'GET', $path);
    }

    /**
     * @return array<string, mixed>
     */
    public function createOrder(Booking $booking, SupplierConnection $connection, User $actor): array
    {
        $payload = $this->orderRequestBuilder->build($booking);
        unset($actor);

        return $this->send($connection, 'POST', $this->path('orders_path'), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId, SupplierConnection $connection): array
    {
        $path = str_replace('{id}', rawurlencode($orderId), $this->path('order_show_path'));

        return $this->send($connection, 'GET', $path);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function send(
        SupplierConnection $connection,
        string $method,
        string $path,
        array $payload = [],
        array $query = [],
    ): array {
        $token = trim((string) (($connection->credentials ?? [])['access_token'] ?? ''));
        if ($token === '') {
            throw new DuffelProviderException('supplier_auth_failed', 401, 'Duffel credentials are not configured.');
        }

        $correlationId = (string) Str::uuid();
        $request = $this->http($connection, $token, $correlationId);
        $url = $this->url($connection, $path);
        $startedAt = microtime(true);

        try {
            $response = $method === 'GET'
                ? $request->get($url, $query)
                : $request->post($url, $payload);
        } catch (ConnectionException $exception) {
            throw new DuffelProviderException(
                'supplier_transport_failed',
                503,
                'Duffel is temporarily unavailable. Please try again.',
                previous: $exception
            );
        }

        $status = $response->status();
        if (in_array($status, [401, 403], true)) {
            throw new DuffelProviderException('supplier_auth_failed', $status, 'Duffel authentication failed.');
        }
        if ($status === 422) {
            throw new DuffelProviderException('supplier_request_invalid', $status, 'Duffel request validation failed.');
        }
        if ($status === 429) {
            throw new DuffelProviderException('supplier_rate_limited', $status, 'Duffel is rate-limiting requests.');
        }
        if ($status >= 500) {
            throw new DuffelProviderException('supplier_transport_failed', $status, 'Duffel service is unavailable.');
        }
        if (! $response->successful()) {
            throw new DuffelProviderException('supplier_provider_error', $status, 'Duffel request failed.');
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new DuffelProviderException('supplier_malformed_response', 502, 'Duffel returned an invalid response.');
        }

        $json['_ota_diagnostic'] = [
            'correlation_id' => $correlationId,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'http_status' => $status,
        ];

        return $json;
    }

    private function http(SupplierConnection $connection, string $token, string $correlationId): PendingRequest
    {
        $credentials = is_array($connection->credentials) ? $connection->credentials : [];
        $version = trim((string) ($credentials['api_version'] ?? config('suppliers.duffel.api_version', 'v2')));
        $headerName = (string) config('suppliers.duffel.api_version_header', 'Duffel-Version');

        return Http::timeout((int) config('suppliers.duffel.timeout_seconds', 30))
            ->connectTimeout((int) config('suppliers.duffel.connect_timeout_seconds', 10))
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                $headerName => $version !== '' ? $version : 'v2',
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }

    private function path(string $key): string
    {
        return (string) config('suppliers.duffel.'.$key);
    }

    private function url(SupplierConnection $connection, string $path): string
    {
        $base = rtrim((string) ($connection->base_url ?: config('suppliers.duffel.default_base_url')), '/');

        return $base.'/'.ltrim($path, '/');
    }
}
