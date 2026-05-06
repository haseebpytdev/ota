<?php

namespace App\Services\FlightSearch;

use App\Data\FlightSearchRequestData;
use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\SupplierConnection;
use App\Services\Pricing\PricingRuleService;
use App\Services\Suppliers\SupplierAdapterResolver;

class FlightSearchService
{
    public function __construct(
        protected SupplierAdapterResolver $resolver,
        protected PricingRuleService $pricingRuleService,
        protected FlightDeparturePolicy $departurePolicy,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return list<array<string, mixed>>
     */
    public function search(array $criteria, ?Agency $agency = null, string $sourceChannel = 'public_guest', ?int $agentId = null): array
    {
        return $this->searchWithMeta($criteria, $agency, $sourceChannel, $agentId)['offers'];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{offers: list<array<string, mixed>>, warnings: list<string>}
     */
    public function searchWithMeta(array $criteria, ?Agency $agency = null, string $sourceChannel = 'public_guest', ?int $agentId = null): array
    {
        $agency ??= Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
        $request = FlightSearchRequestData::fromArray($criteria, $agency?->id, $sourceChannel);
        if ($agency === null) {
            $connections = collect([
                new SupplierConnection([
                    'provider' => SupplierProvider::Mock,
                    'status' => SupplierConnectionStatus::Active,
                    'is_active' => true,
                ]),
            ]);
        } else {
            $connections = SupplierConnection::query()
                ->where('agency_id', $agency->id)
                ->where(function ($query): void {
                    $query->where('is_active', true)
                        ->orWhere('status', SupplierConnectionStatus::Active->value);
                })
                ->orderBy('id')
                ->get();
        }

        if ($connections->isEmpty()) {
            return [
                'offers' => [],
                'warnings' => [],
            ];
        }

        $offers = [];
        $warnings = [];

        foreach ($connections as $connection) {
            $adapter = $this->resolver->resolve($connection->provider);
            $result = $adapter->search($request, $connection);
            $warnings = [...$warnings, ...$result->warnings];

            foreach ($result->offers as $offerData) {
                $offer = $offerData->toArray();
                $fare = $offer['fare_breakdown'] ?? [];
                $pricing = $agency !== null
                    ? $this->pricingRuleService->calculateMarkup($agency, [
                        'base_fare' => (float) ($fare['base_fare'] ?? 0),
                        'taxes' => (float) ($fare['taxes'] ?? 0),
                        'currency' => $fare['currency'] ?? 'PKR',
                    ], [
                        'route' => $request->origin.'-'.$request->destination,
                        'origin' => $request->origin,
                        'destination' => $request->destination,
                        'airline' => strtolower((string) ($offer['airline_code'] ?? '')),
                        'supplier' => $offer['supplier_provider'] ?? $connection->provider->value,
                        'agent_id' => $agentId,
                        'cabin' => $offer['cabin'] ?? null,
                        'fare_family' => $offer['fare_family'] ?? null,
                        'travel_date' => $request->departure_date,
                        'source_channel' => $sourceChannel,
                    ])
                    : $this->defaultPricing($fare);

                $offers[] = $this->toDisplayOffer($offer, $pricing);
            }
        }

        [$offers, $leadWarning] = $this->departurePolicy->filterOffersForLeadTime($criteria, $offers);
        if ($leadWarning !== null) {
            $warnings[] = $leadWarning;
        }

        return [
            'offers' => $offers,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>
     */
    protected function toDisplayOffer(array $offer, array $pricing): array
    {
        $durationMinutes = (int) ($offer['duration_minutes'] ?? 0);
        $baggageSummary = is_array($offer['baggage'] ?? null)
            ? (string) (($offer['baggage']['summary'] ?? '') ?: ($offer['baggage']['checked'] ?? ''))
            : (string) ($offer['baggage'] ?? '');
        $fare = $offer['fare_breakdown'] ?? [];
        $airlineCode = (string) ($offer['airline_code'] ?? 'XX');

        return array_merge($offer, [
            'id' => $offer['offer_id'],
            'depart_at' => $offer['departure_at'],
            'arrive_at' => $offer['arrival_at'],
            'carrier_code' => $airlineCode,
            'duration_h' => intdiv($durationMinutes, 60),
            'duration_m' => $durationMinutes % 60,
            'baggage' => $baggageSummary,
            'base_fare' => (float) ($pricing['base_fare'] ?? ($fare['base_fare'] ?? 0)),
            'currency' => (string) ($pricing['pricing_currency'] ?? ($fare['currency'] ?? 'PKR')),
            'taxes' => (float) ($pricing['taxes'] ?? 0),
            'supplier_total_source' => (float) ($pricing['supplier_total_source'] ?? (($fare['base_fare'] ?? 0) + ($fare['taxes'] ?? 0))),
            'markup' => (float) ($pricing['admin_markup'] ?? 0)
                + (float) ($pricing['route_markup'] ?? 0)
                + (float) ($pricing['airline_markup'] ?? 0)
                + (float) ($pricing['agent_markup_or_commission'] ?? 0),
            'service_fee' => (float) ($pricing['service_fee'] ?? 0),
            'total' => (float) ($pricing['final_total'] ?? 0),
            'final_customer_price' => (float) ($pricing['final_total'] ?? 0),
            'pricing_currency' => (string) ($pricing['pricing_currency'] ?? ($fare['currency'] ?? 'PKR')),
            'supplier_currency' => (string) ($pricing['supplier_currency'] ?? ($fare['currency'] ?? 'PKR')),
            'conversion_status' => (string) ($pricing['conversion_status'] ?? 'same_currency'),
            'applied_rules' => $pricing['applied_rules'] ?? [],
            'pricing_components' => $pricing,
        ]);
    }

    /**
     * @param  array<string, mixed>  $fare
     * @return array<string, mixed>
     */
    protected function defaultPricing(array $fare): array
    {
        $baseFare = (float) ($fare['base_fare'] ?? 0);
        $taxes = (float) ($fare['taxes'] ?? 0);
        $supplierTotal = $baseFare + $taxes;
        $serviceFee = 2499.0;
        $markup = round($baseFare * 0.035, 2);

        return [
            'base_fare' => $baseFare,
            'taxes' => $taxes,
            'supplier_total' => $supplierTotal,
            'admin_markup' => $markup,
            'route_markup' => 0.0,
            'airline_markup' => 0.0,
            'agent_markup_or_commission' => 0.0,
            'service_fee' => $serviceFee,
            'final_total' => $supplierTotal + $markup + $serviceFee,
            'applied_rules' => [],
        ];
    }
}
