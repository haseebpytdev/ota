<?php

namespace App\Services\Suppliers;

use App\Data\FlightSearchRequestData;
use App\Data\NormalizedFlightOfferData;
use App\Data\OfferValidationResultData;
use App\Enums\SupplierConnectionStatus;
use App\Models\Agency;
use App\Models\SupplierConnection;
use App\Services\Pricing\PricingRuleService;

class OfferValidationService
{
    public function __construct(
        protected SupplierAdapterResolver $resolver,
        protected PricingRuleService $pricingRuleService,
    ) {}

    /**
     * @param  array<string, mixed>  $selectedOfferSnapshot
     * @param  array<string, mixed>  $searchContext
     */
    public function validateSelectedOffer(Agency $agency, array $selectedOfferSnapshot, array $searchContext): OfferValidationResultData
    {
        $provider = (string) ($selectedOfferSnapshot['supplier_provider'] ?? '');
        $connection = $this->resolveConnection($agency, $selectedOfferSnapshot);
        if ($connection === null) {
            return new OfferValidationResultData(
                is_valid: false,
                status: 'provider_error',
                original_offer_id: (string) ($selectedOfferSnapshot['offer_id'] ?? $selectedOfferSnapshot['id'] ?? ''),
                warnings: ['Selected fare is no longer available. Please choose another option.']
            );
        }

        $request = FlightSearchRequestData::fromArray($searchContext, $agency->id, (string) ($searchContext['source_channel'] ?? 'public_guest'));
        $adapter = $this->resolver->resolve($connection->provider);
        $sourceOffer = NormalizedFlightOfferData::fromArray($selectedOfferSnapshot);
        $validation = $adapter->validateOffer($sourceOffer, $request, $connection);

        if ($validation->validated_offer === null) {
            return $validation;
        }

        $validatedArray = $validation->validated_offer->toArray();
        $fare = $validatedArray['fare_breakdown'] ?? [];
        $pricing = $this->pricingRuleService->calculateMarkup($agency, [
            'base_fare' => (float) ($fare['base_fare'] ?? 0),
            'taxes' => (float) ($fare['taxes'] ?? 0),
            'currency' => (string) ($fare['currency'] ?? 'PKR'),
        ], [
            'route' => strtoupper((string) ($request->origin)).'-'.strtoupper((string) ($request->destination)),
            'origin' => $request->origin,
            'destination' => $request->destination,
            'airline' => strtolower((string) ($validatedArray['airline_code'] ?? '')),
            'supplier' => $provider !== '' ? $provider : $connection->provider->value,
            'agent_id' => $searchContext['agent_id'] ?? null,
            'cabin' => $validatedArray['cabin'] ?? null,
            'fare_family' => $validatedArray['fare_family'] ?? null,
            'travel_date' => $request->departure_date,
            'source_channel' => $request->source_channel,
        ]);

        $validation->meta = array_merge($validation->meta, [
            'pricing_snapshot' => $pricing,
            'applied_rules' => $pricing['applied_rules'] ?? [],
            'final_customer_price' => (float) ($pricing['final_total'] ?? 0),
        ]);
        $validation->new_total = (float) ($pricing['final_total'] ?? $validation->new_total);
        $validation->currency = (string) ($fare['currency'] ?? $validation->currency ?? 'PKR');

        return $validation;
    }

    /**
     * @param  array<string, mixed>  $selectedOfferSnapshot
     */
    protected function resolveConnection(Agency $agency, array $selectedOfferSnapshot): ?SupplierConnection
    {
        $connectionId = $selectedOfferSnapshot['supplier_connection_id'] ?? null;
        if ($connectionId !== null) {
            return SupplierConnection::query()
                ->where('agency_id', $agency->id)
                ->where('id', (int) $connectionId)
                ->where(function ($query): void {
                    $query->where('is_active', true)
                        ->orWhere('status', SupplierConnectionStatus::Active->value);
                })
                ->first();
        }

        $provider = (string) ($selectedOfferSnapshot['supplier_provider'] ?? '');
        if ($provider === '') {
            return null;
        }

        return SupplierConnection::query()
            ->where('agency_id', $agency->id)
            ->where('provider', $provider)
            ->where(function ($query): void {
                $query->where('is_active', true)
                    ->orWhere('status', SupplierConnectionStatus::Active->value);
            })
            ->first();
    }
}
