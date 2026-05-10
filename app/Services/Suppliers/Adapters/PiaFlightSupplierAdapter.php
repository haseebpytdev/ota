<?php

namespace App\Services\Suppliers\Adapters;

use App\Contracts\Suppliers\FlightSupplierInterface;
use App\Data\FlightSearchRequestData;
use App\Data\FlightSearchResultData;
use App\Data\NormalizedFlightOfferData;
use App\Data\OfferValidationResultData;
use App\Enums\SupplierProvider;
use App\Models\SupplierConnection;

class PiaFlightSupplierAdapter implements FlightSupplierInterface
{
    public function search(FlightSearchRequestData $request, SupplierConnection $connection): FlightSearchResultData
    {
        return new FlightSearchResultData(
            supplier_provider: SupplierProvider::Pia,
            offers: [],
            warnings: ['Provider adapter is configured but live search is not implemented yet.'],
            meta: ['connection_id' => $connection->id]
        );
    }

    public function provider(): SupplierProvider
    {
        return SupplierProvider::Pia;
    }

    public function validateOffer(NormalizedFlightOfferData|string $offer, FlightSearchRequestData $request, SupplierConnection $connection): OfferValidationResultData
    {
        return new OfferValidationResultData(
            is_valid: false,
            status: 'not_supported',
            original_offer_id: is_string($offer) ? $offer : $offer->offer_id,
            warnings: ['PIA offer validation is not supported yet.']
        );
    }
}
