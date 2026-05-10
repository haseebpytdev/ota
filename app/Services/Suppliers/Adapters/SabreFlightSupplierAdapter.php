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
use App\Services\Suppliers\Sabre\SabreClient;
use App\Services\Suppliers\Sabre\SabreFlightSearchNormalizer;
use Throwable;

class SabreFlightSupplierAdapter implements FlightSupplierInterface
{
    public function __construct(
        protected SabreClient $client,
        protected SabreFlightSearchNormalizer $normalizer,
    ) {}

    public function search(FlightSearchRequestData $request, SupplierConnection $connection): FlightSearchResultData
    {
        if (! $connection->isActive() || $connection->status !== SupplierConnectionStatus::Active) {
            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Sabre,
                offers: [],
                warnings: ['Sabre supplier connection is inactive.'],
                meta: ['connection_id' => $connection->id]
            );
        }

        if (! in_array($connection->environment, [SupplierEnvironment::Sandbox, SupplierEnvironment::Live], true)) {
            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Sabre,
                offers: [],
                warnings: ['Sabre search is only enabled for sandbox or live environments.'],
                meta: ['connection_id' => $connection->id]
            );
        }

        $credentials = is_array($connection->credentials) ? $connection->credentials : [];
        if (trim((string) ($credentials['client_id'] ?? '')) === '' || trim((string) ($credentials['client_secret'] ?? '')) === '') {
            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Sabre,
                offers: [],
                warnings: ['Sabre credentials are not configured.'],
                meta: ['connection_id' => $connection->id]
            );
        }

        try {
            $response = $this->client->searchFlights($request, $connection);
            $offers = $this->normalizer->normalize($response, $connection);
            if ($offers === []) {
                return new FlightSearchResultData(
                    supplier_provider: SupplierProvider::Sabre,
                    offers: [],
                    warnings: ['No Sabre offers were returned for this search.'],
                    meta: ['connection_id' => $connection->id]
                );
            }

            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Sabre,
                offers: $offers,
                warnings: [],
                meta: ['connection_id' => $connection->id]
            );
        } catch (Throwable) {
            return new FlightSearchResultData(
                supplier_provider: SupplierProvider::Sabre,
                offers: [],
                warnings: ['Sabre search is temporarily unavailable. Please try again later.'],
                meta: ['connection_id' => $connection->id]
            );
        }
    }

    public function provider(): SupplierProvider
    {
        return SupplierProvider::Sabre;
    }

    public function validateOffer(NormalizedFlightOfferData|string $offer, FlightSearchRequestData $request, SupplierConnection $connection): OfferValidationResultData
    {
        $original = is_string($offer) ? null : $offer;
        $originalOfferId = is_string($offer) ? $offer : $offer->offer_id;

        $searchResult = $this->search($request, $connection);
        if ($searchResult->warnings !== []) {
            return new OfferValidationResultData(
                is_valid: false,
                status: 'provider_error',
                original_offer_id: $originalOfferId,
                warnings: ['Sabre fare validation is temporarily unavailable. Please try again.']
            );
        }

        $matched = $this->matchReplayOffer($searchResult->offers, $offer);
        if ($matched === null) {
            return new OfferValidationResultData(
                is_valid: false,
                status: 'unavailable',
                original_offer_id: $originalOfferId,
                warnings: ['Selected fare is no longer available. Please choose another option.']
            );
        }

        $oldTotal = $original?->fare_breakdown->supplier_total;
        $newTotal = $matched->fare_breakdown->supplier_total;
        $priceChanged = $oldTotal !== null && abs($oldTotal - $newTotal) > 0.009;

        return new OfferValidationResultData(
            is_valid: ! $priceChanged,
            status: $priceChanged ? 'price_changed' : 'valid',
            original_offer_id: $originalOfferId,
            validated_offer: $matched,
            price_changed: $priceChanged,
            old_total: $oldTotal,
            new_total: $newTotal,
            currency: $matched->fare_breakdown->currency,
            warnings: $priceChanged ? ['Fare changed during validation. Please review the updated fare before continuing.'] : []
        );
    }

    /**
     * @param  list<NormalizedFlightOfferData>  $offers
     */
    protected function matchReplayOffer(array $offers, NormalizedFlightOfferData|string $source): ?NormalizedFlightOfferData
    {
        $sourceOffer = is_string($source) ? null : $source;
        $sourceId = is_string($source) ? $source : $source->offer_id;

        foreach ($offers as $candidate) {
            if ($candidate->offer_id === $sourceId) {
                return $candidate;
            }

            if ($sourceOffer === null) {
                continue;
            }

            if (
                $candidate->airline_code === $sourceOffer->airline_code
                && $candidate->origin === $sourceOffer->origin
                && $candidate->destination === $sourceOffer->destination
                && $candidate->departure_at === $sourceOffer->departure_at
                && ($candidate->flight_number ?? '') === ($sourceOffer->flight_number ?? '')
                && strtolower($candidate->cabin) === strtolower($sourceOffer->cabin)
            ) {
                return $candidate;
            }
        }

        return null;
    }
}
