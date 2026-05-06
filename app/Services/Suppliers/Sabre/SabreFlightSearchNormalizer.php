<?php

namespace App\Services\Suppliers\Sabre;

use App\Data\BaggageAllowanceData;
use App\Data\FareBreakdownData;
use App\Data\FlightSegmentData;
use App\Data\NormalizedFlightOfferData;
use App\Enums\SupplierProvider;
use App\Models\SupplierConnection;

class SabreFlightSearchNormalizer
{
    /**
     * @param  array<string, mixed>  $response
     * @return list<NormalizedFlightOfferData>
     */
    public function normalize(array $response, SupplierConnection $connection): array
    {
        $offers = data_get($response, 'groupedItineraryResponse.itineraryGroups.0.itineraries', []);
        if (! is_array($offers)) {
            return [];
        }

        $normalized = [];
        foreach ($offers as $itinerary) {
            if (! is_array($itinerary)) {
                continue;
            }

            $legs = data_get($itinerary, 'legs.0', []);
            $schedule = data_get($itinerary, 'scheduleDescs.0', []);
            $pricing = data_get($itinerary, 'pricingInformation.0.fare', []);

            $origin = (string) (data_get($schedule, 'departure.airport') ?? data_get($legs, 'origin') ?? '');
            $destination = (string) (data_get($schedule, 'arrival.airport') ?? data_get($legs, 'destination') ?? '');
            $departureAt = (string) (data_get($schedule, 'departure.time') ?? '');
            $arrivalAt = (string) (data_get($schedule, 'arrival.time') ?? '');

            $baseFare = (float) (data_get($pricing, 'baseFareAmount') ?? 0);
            $taxes = (float) (data_get($pricing, 'taxAmount') ?? 0);
            $total = (float) (data_get($pricing, 'totalFare') ?? ($baseFare + $taxes));
            $currency = (string) (data_get($pricing, 'currency') ?? 'PKR');

            $airlineCode = (string) (data_get($schedule, 'carrier.marketing') ?? data_get($schedule, 'carrier.operating') ?? 'XX');
            $flightNumber = (string) (data_get($schedule, 'carrier.marketingFlightNumber') ?? '');
            $rawReference = (string) (data_get($itinerary, 'id') ?? '');

            $segment = new FlightSegmentData(
                origin: $origin,
                destination: $destination,
                departure_at: $departureAt,
                arrival_at: $arrivalAt,
                flight_number: $flightNumber,
                airline_code: $airlineCode,
                airline_name: null,
                duration_minutes: (int) (data_get($schedule, 'elapsedTime') ?? 0),
            );

            $offerId = hash('sha256', implode('|', [
                SupplierProvider::Sabre->value,
                $connection->id,
                $rawReference,
                $airlineCode.$flightNumber,
                $departureAt,
                $total,
                $currency,
            ]));

            $normalized[] = new NormalizedFlightOfferData(
                offer_id: $offerId,
                supplier_provider: SupplierProvider::Sabre->value,
                supplier_connection_id: $connection->id,
                airline_code: $airlineCode,
                airline_name: (string) (data_get($schedule, 'carrier.marketing') ?? $airlineCode),
                flight_number: $flightNumber !== '' ? $flightNumber : null,
                origin: $origin,
                destination: $destination,
                departure_at: $departureAt,
                arrival_at: $arrivalAt,
                duration_minutes: (int) (data_get($schedule, 'elapsedTime') ?? 0),
                stops: max(0, (int) count((array) data_get($itinerary, 'legs', [])) - 1),
                cabin: strtolower((string) (data_get($pricing, 'passengerInfoList.0.passengerInfo.fareComponents.0.segments.0.segment.cabinCode') ?? 'economy')),
                fare_family: null,
                refundable: (bool) (data_get($pricing, 'passengerInfoList.0.passengerInfo.nonRefundable') === false),
                seats_left: null,
                segments: [$segment->toArray()],
                baggage: new BaggageAllowanceData(
                    summary: (string) (data_get($pricing, 'passengerInfoList.0.passengerInfo.baggageInformation.0.allowance.ref') ?? 'As per fare rule')
                ),
                fare_breakdown: new FareBreakdownData(
                    base_fare: $baseFare,
                    taxes: $taxes,
                    supplier_fees: max(0, $total - ($baseFare + $taxes)),
                    supplier_total: $total,
                    currency: $currency
                ),
                raw_reference: $rawReference !== '' ? $rawReference : null,
                raw_payload: [
                    'itinerary_id' => $rawReference,
                    'airline_code' => $airlineCode,
                    'flight_number' => $flightNumber,
                    'departure_at' => $departureAt,
                    'arrival_at' => $arrivalAt,
                    'fare' => [
                        'base' => $baseFare,
                        'tax' => $taxes,
                        'total' => $total,
                        'currency' => $currency,
                    ],
                ]
            );
        }

        return $normalized;
    }
}
