<?php

namespace App\Enums;

enum SupplierProvider: string
{
    case Sabre = 'sabre';
    case Pia = 'pia';
    case AirlineDirect = 'airline_direct';
    case Duffel = 'duffel';
    case Amadeus = 'amadeus';
    case Travelport = 'travelport';
}
