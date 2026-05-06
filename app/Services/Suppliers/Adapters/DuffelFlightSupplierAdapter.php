<?php

namespace App\Services\Suppliers\Adapters;

use App\Contracts\Suppliers\FlightSupplierInterface;
use App\Data\FlightSearchRequestData;
use App\Data\FlightSearchResultData;
use App\Data\NormalizedFlightOfferData;
use App\Data\OfferValidationResultData;
use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierEnvironment;
use App\Enums\SupplierProvider;
use App\Models\SupplierConnection;
use App\Services\Suppliers\Duffel\DuffelClient;
use App\Services\Suppliers\Duffel\DuffelOfferNormalizer;
use App\Services\Suppliers\Duffel\DuffelProviderException;
use App\Services\Suppliers\SupplierDiagnosticLogger;
use Carbon\Carbon;
use Throwable;

class DuffelFlightSupplierAdapter implements FlightSupplierInterface
{
    public function __construct(
        private readonly DuffelClient $client,
        private readonly DuffelOfferNormalizer $normalizer,
        private readonly SupplierDiagnosticLogger $diagnosticLogger,
    ) {}

    public function search(FlightSearchRequestData $request, SupplierConnection $connection): FlightSearchResultData
    {
        if (! $this->connectionReady($connection)) {
            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'search',
                status: 'warning',
                safeMessage: 'Connection is inactive for Duffel search.'
            );

            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Duffel,
                offers: [],
                warnings: ['Duffel supplier connection is inactive.'],
                meta: ['connection_id' => $connection->id]
            );
        }

        if (! $this->tokenExists($connection)) {
            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'search',
                status: 'failed',
                safeMessage: 'Missing Duffel access token.'
            );

            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Duffel,
                offers: [],
                warnings: ['Provider search is temporarily unavailable.'],
                meta: ['connection_id' => $connection->id]
            );
        }

        try {
            $response = $this->client->createOfferRequest($request, $connection);
            $diagnostic = is_array($response['_ota_diagnostic'] ?? null) ? $response['_ota_diagnostic'] : [];
            $offers = $this->normalizer->normalizeMany($response, $connection);
            if ($offers === []) {
                $this->diagnosticLogger->log(
                    connection: $connection,
                    action: 'search',
                    status: 'warning',
                    durationMs: isset($diagnostic['duration_ms']) ? (int) $diagnostic['duration_ms'] : null,
                    safeMessage: 'Duffel returned no fares for the route/date.',
                    correlationId: isset($diagnostic['correlation_id']) ? (string) $diagnostic['correlation_id'] : null
                );

                return new FlightSearchResultData(
                    supplier_provider: SupplierProvider::Duffel,
                    offers: [],
                    warnings: ['Duffel returned no fares for this route/date.'],
                    meta: ['connection_id' => $connection->id]
                );
            }

            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'search',
                status: 'success',
                durationMs: isset($diagnostic['duration_ms']) ? (int) $diagnostic['duration_ms'] : null,
                safeMessage: 'Duffel search completed.',
                correlationId: isset($diagnostic['correlation_id']) ? (string) $diagnostic['correlation_id'] : null,
                meta: [
                    'offers_count' => count($offers),
                ],
            );

            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Duffel,
                offers: $offers,
                warnings: [],
                meta: ['connection_id' => $connection->id]
            );
        } catch (DuffelProviderException $exception) {
            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'search',
                status: 'failed',
                safeMessage: $exception->safeMessage,
                meta: [
                    'error_code' => $exception->normalizedCode,
                ],
            );

            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Duffel,
                offers: [],
                warnings: ['Provider search is temporarily unavailable.'],
                meta: ['connection_id' => $connection->id, 'error_code' => $exception->normalizedCode]
            );
        } catch (Throwable) {
            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'search',
                status: 'failed',
                safeMessage: 'Duffel search transport error.'
            );

            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Duffel,
                offers: [],
                warnings: ['Provider search is temporarily unavailable.'],
                meta: ['connection_id' => $connection->id]
            );
        }
    }

    public function validateOffer(NormalizedFlightOfferData|string $offer, FlightSearchRequestData $request, SupplierConnection $connection): OfferValidationResultData
    {
        $original = is_string($offer) ? null : $offer;
        $offerId = is_string($offer)
            ? $offer
            : ((string) ($offer->raw_reference ?: $offer->offer_id));

        if (! $this->connectionReady($connection) || ! $this->tokenExists($connection)) {
            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'validate_offer',
                status: 'failed',
                safeMessage: 'Duffel provider is not ready for validation.',
                meta: ['offer_id' => $offerId]
            );

            return new OfferValidationResultData(
                is_valid: false,
                status: 'provider_error',
                original_offer_id: $offerId,
                warnings: ['Duffel provider is not ready for validation.']
            );
        }

        try {
            $response = $this->client->getOffer($offerId, $connection);
            $diagnostic = is_array($response['_ota_diagnostic'] ?? null) ? $response['_ota_diagnostic'] : [];
            $validated = $this->normalizer->normalizeOne($response, $connection);
            if ($validated === null) {
                $this->diagnosticLogger->log(
                    connection: $connection,
                    action: 'validate_offer',
                    status: 'warning',
                    durationMs: isset($diagnostic['duration_ms']) ? (int) $diagnostic['duration_ms'] : null,
                    safeMessage: 'Duffel offer is unavailable.',
                    correlationId: isset($diagnostic['correlation_id']) ? (string) $diagnostic['correlation_id'] : null,
                    meta: ['offer_id' => $offerId]
                );

                return new OfferValidationResultData(
                    is_valid: false,
                    status: 'unavailable',
                    original_offer_id: $offerId,
                    warnings: ['Selected Duffel fare is no longer available.']
                );
            }

            if ($validated->expires_at !== null) {
                try {
                    if (Carbon::parse($validated->expires_at)->isPast()) {
                        $this->diagnosticLogger->log(
                            connection: $connection,
                            action: 'validate_offer',
                            status: 'warning',
                            durationMs: isset($diagnostic['duration_ms']) ? (int) $diagnostic['duration_ms'] : null,
                            safeMessage: 'Duffel offer has expired.',
                            correlationId: isset($diagnostic['correlation_id']) ? (string) $diagnostic['correlation_id'] : null,
                            meta: ['offer_id' => $offerId]
                        );

                        return new OfferValidationResultData(
                            is_valid: false,
                            status: 'expired',
                            original_offer_id: $offerId,
                            warnings: ['Selected Duffel fare has expired.']
                        );
                    }
                } catch (Throwable) {
                    // Ignore parse issues and continue normal comparison.
                }
            }

            $oldTotal = $original?->fare_breakdown->supplier_total;
            $newTotal = $validated->fare_breakdown->supplier_total;
            $priceChanged = $oldTotal !== null && abs($oldTotal - $newTotal) > 0.009;

            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'validate_offer',
                status: $priceChanged ? 'warning' : 'success',
                durationMs: isset($diagnostic['duration_ms']) ? (int) $diagnostic['duration_ms'] : null,
                safeMessage: $priceChanged ? 'Duffel offer price changed.' : 'Duffel offer validated.',
                correlationId: isset($diagnostic['correlation_id']) ? (string) $diagnostic['correlation_id'] : null,
                meta: ['offer_id' => $offerId]
            );

            return new OfferValidationResultData(
                is_valid: ! $priceChanged,
                status: $priceChanged ? 'price_changed' : 'valid',
                original_offer_id: $offerId,
                validated_offer: $validated,
                price_changed: $priceChanged,
                old_total: $oldTotal,
                new_total: $newTotal,
                currency: $validated->fare_breakdown->currency,
                warnings: $priceChanged ? ['Duffel fare changed during validation.'] : []
            );
        } catch (DuffelProviderException $exception) {
            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'validate_offer',
                status: 'failed',
                safeMessage: $exception->safeMessage,
                meta: [
                    'offer_id' => $offerId,
                    'error_code' => $exception->normalizedCode,
                ]
            );

            return new OfferValidationResultData(
                is_valid: false,
                status: 'provider_error',
                original_offer_id: $offerId,
                warnings: [$exception->safeMessage]
            );
        } catch (Throwable) {
            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'validate_offer',
                status: 'failed',
                safeMessage: 'Duffel validation transport error.',
                meta: ['offer_id' => $offerId]
            );

            return new OfferValidationResultData(
                is_valid: false,
                status: 'provider_error',
                original_offer_id: $offerId,
                warnings: ['Duffel validation is temporarily unavailable.']
            );
        }
    }

    public function provider(): SupplierProvider
    {
        return SupplierProvider::Duffel;
    }

    private function connectionReady(SupplierConnection $connection): bool
    {
        if (! $connection->isActive() || $connection->status !== SupplierConnectionStatus::Active) {
            return false;
        }

        return in_array($connection->environment, [SupplierEnvironment::Sandbox, SupplierEnvironment::Demo, SupplierEnvironment::Live], true);
    }

    private function tokenExists(SupplierConnection $connection): bool
    {
        $credentials = is_array($connection->credentials) ? $connection->credentials : [];

        return trim((string) ($credentials['access_token'] ?? '')) !== '';
    }
}
