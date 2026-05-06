<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Services\FlightSearch\FlightSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightController extends Controller
{
    public function __construct(
        protected FlightSearchService $flightSearch,
    ) {}

    public function search(Request $request): View
    {
        $popular = config('demo-routes.popular', []);
        $first = $popular[0] ?? ['from' => 'LHE', 'to' => 'DXB'];

        return view('frontend.flights.search', [
            'defaults' => [
                'origin' => $request->string('from', $first['from'])->toString(),
                'destination' => $request->string('to', $first['to'])->toString(),
                'depart' => $request->string('depart', now()->addDays(14)->format('Y-m-d'))->toString(),
            ],
        ]);
    }

    public function results(Request $request): View
    {
        $criteria = [
            'origin' => $request->string('from', 'LHE')->toString(),
            'destination' => $request->string('to', 'DXB')->toString(),
            'depart_date' => $request->string('depart', now()->addDays(14)->format('Y-m-d'))->toString(),
        ];

        $agency = Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
        $result = $this->flightSearch->searchWithMeta($criteria, $agency, 'public_guest');

        return view('frontend.flights.results', [
            'criteria' => $criteria,
            'offers' => $result['offers'],
            'warnings' => $result['warnings'],
        ]);
    }

    public function details(Request $request, string $id): View
    {
        $criteria = [
            'origin' => $request->string('from', 'LHE')->toString(),
            'destination' => $request->string('to', 'DXB')->toString(),
            'depart_date' => $request->string('depart', now()->addDays(14)->format('Y-m-d'))->toString(),
        ];

        $agency = Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
        $enriched = $this->flightSearch->search($criteria, $agency, 'public_guest');
        $offer = collect($enriched)->firstWhere('id', $id);

        abort_if($offer === null, 404);

        return view('frontend.flights.details', [
            'offer' => $offer,
            'criteria' => $criteria,
        ]);
    }
}
