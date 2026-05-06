<?php

namespace App\Services\Suppliers\Adapters;

use App\Contracts\Suppliers\FlightSupplierInterface;
use App\Data\BaggageAllowanceData;
use App\Data\FareBreakdownData;
use App\Data\FlightSearchRequestData;
use App\Data\FlightSearchResultData;
use App\Data\FlightSegmentData;
use App\Data\NormalizedFlightOfferData;
use App\Data\OfferValidationResultData;
use App\Enums\SupplierProvider;
use App\Models\SupplierConnection;
use App\Services\Suppliers\Mock\MockFlightSupplier;

class MockFlightSupplierAdapter implements FlightSupplierInterface
{
    public function __construct(
        protected MockFlightSupplier $mockSupplier,
    ) {}

    public function search(FlightSearchRequestData $request, SupplierConnection $connection): FlightSearchResultData
    {
        $rawOffers = $this->mockSupplier->search([
            'origin' => $request->origin,
            'destination' => $request->destination,
            'depart_date' => $request->departure_date,
        ]);
        $meta = config('demo-flights.offers', []);

        $offers = array_map(function (array $rawOffer) use ($connection, $meta): NormalizedFlightOfferData {
            $offerId = (string) ($rawOffer['id'] ?? 'mock-unknown');
            $overlay = $meta[$offerId] ?? [];
            $origin = (string) ($rawOffer['origin'] ?? '');
            $destination = (string) ($rawOffer['destination'] ?? '');
            $departureAt = (string) ($rawOffer['depart_at'] ?? now()->toIso8601String());
            $arrivalAt = (string) ($rawOffer['arrive_at'] ?? now()->toIso8601String());
            $durationMinutes = (int) ($rawOffer['duration_minutes'] ?? 0);
            $baseFare = (float) ($rawOffer['base_fare'] ?? 0);
            $currency = (string) ($rawOffer['currency'] ?? 'PKR');
            $taxes = round($baseFare * 0.08, 2);
            $supplierFees = 0.0;

            $segment = new FlightSegmentData(
                origin: $origin,
                destination: $destination,
                departure_at: $departureAt,
                arrival_at: $arrivalAt,
                flight_number: (string) ($rawOffer['flight_number'] ?? ''),
                airline_code: (string) ($rawOffer['carrier_code'] ?? 'XX'),
                airline_name: (string) ($overlay['airline_name'] ?? 'Mock Airline'),
                duration_minutes: $durationMinutes
            );

            return new NormalizedFlightOfferData(
                offer_id: $offerId,
                supplier_provider: SupplierProvider::Mock->value,
                supplier_connection_id: $connection->id,
                airline_code: (string) ($overlay['airline_code'] ?? ($rawOffer['carrier_code'] ?? 'XX')),
                airline_name: (string) ($overlay['airline_name'] ?? 'Mock Airline'),
                flight_number: (string) ($rawOffer['flight_number'] ?? ''),
                origin: $origin,
                destination: $destination,
                departure_at: $departureAt,
                arrival_at: $arrivalAt,
                duration_minutes: $durationMinutes,
                stops: 0,
                cabin: (string) ($rawOffer['cabin'] ?? 'economy'),
                fare_family: isset($overlay['fare_family']) ? (string) $overlay['fare_family'] : null,
                refundable: (bool) ($overlay['refundable'] ?? false),
                seats_left: isset($overlay['seats_left']) ? (int) $overlay['seats_left'] : null,
                segments: [$segment->toArray()],
                baggage: new BaggageAllowanceData(summary: isset($overlay['baggage']) ? (string) $overlay['baggage'] : 'As per fare rule'),
                fare_breakdown: new FareBreakdownData(
                    base_fare: $baseFare,
                    taxes: $taxes,
                    supplier_fees: $supplierFees,
                    supplier_total: $baseFare + $taxes + $supplierFees,
                    currency: $currency
                ),
                raw_reference: $offerId,
                raw_payload: [
                    'carrier_code' => $rawOffer['carrier_code'] ?? null,
                ]
            );
        }, $rawOffers);

        return new FlightSearchResultData(
            supplier_provider: SupplierProvider::Mock,
            offers: $offers,
            warnings: [],
            meta: ['connection_id' => $connection->id]
        );
    }

    public function provider(): SupplierProvider
    {
        return SupplierProvider::Mock;
    }

    public function validateOffer(NormalizedFlightOfferData|string $offer, FlightSearchRequestData $request, SupplierConnection $connection): OfferValidationResultData
    {
        $offerId = is_string($offer) ? $offer : $offer->offer_id;
        $result = $this->search($request, $connection);
        $matched = collect($result->offers)->first(fn (NormalizedFlightOfferData $item): bool => $item->offer_id === $offerId);

        if ($matched === null) {
            return new OfferValidationResultData(
                is_valid: false,
                status: 'unavailable',
                original_offer_id: $offerId,
                warnings: ['Selected fare is no longer available. Please choose another option.']
            );
        }

        $forcePriceChange = (bool) data_get($connection->settings ?? [], 'force_price_change', false);
        if ($forcePriceChange) {
            $adjusted = $matched->toArray();
            $adjustedFare = $adjusted['fare_breakdown'] ?? [];
            $adjustedFare['base_fare'] = (float) ($adjustedFare['base_fare'] ?? 0) + 750;
            $adjustedFare['supplier_total'] = (float) ($adjustedFare['base_fare'] ?? 0) + (float) ($adjustedFare['taxes'] ?? 0) + (float) ($adjustedFare['supplier_fees'] ?? 0);
            $adjusted['fare_breakdown'] = $adjustedFare;
            $matched = NormalizedFlightOfferData::fromArray($adjusted);
        }

        $oldTotal = is_string($offer) ? null : $offer->fare_breakdown->supplier_total;
        $newTotal = $matched->fare_breakdown->supplier_total;
        $priceChanged = $oldTotal !== null && abs($oldTotal - $newTotal) > 0.009;

        return new OfferValidationResultData(
            is_valid: ! $priceChanged,
            status: $priceChanged ? 'price_changed' : 'valid',
            original_offer_id: $offerId,
            validated_offer: $matched,
            price_changed: $priceChanged,
            old_total: $oldTotal,
            new_total: $newTotal,
            currency: $matched->fare_breakdown->currency,
            warnings: $priceChanged ? ['Fare changed during validation. Please review the updated fare before continuing.'] : [],
        );
    }
}
