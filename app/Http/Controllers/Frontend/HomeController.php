<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Agencies\AgencyBrandingService;
use App\Services\FlightSearch\FlightSearchService;
use App\Services\Pricing\FlightPricingService;
use App\Support\Branding\SafeBrandingResolver;
use App\Support\DemoFlightOffers;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{
    public function __construct(
        protected FlightSearchService $flightSearch,
        protected FlightPricingService $pricing,
        protected AgencyBrandingService $brandingService,
    ) {}

    public function index(): View
    {
        $popular = config('demo-routes.popular', []);
        $first = $popular[0] ?? ['from' => 'LHE', 'to' => 'DXB'];
        $defaultDepart = now()->addDays(14)->format('Y-m-d');
        $defaultOrigin = $first['from'];
        $defaultDestination = $first['to'];

        $criteria = [
            'origin' => $defaultOrigin,
            'destination' => $defaultDestination,
            'depart_date' => $defaultDepart,
        ];
        $offers = [];
        try {
            $offers = DemoFlightOffers::withDemoMeta($this->pricing->applyToOffers($this->flightSearch->search($criteria)));
        } catch (Throwable $e) {
            Log::warning('Homepage flight preview fallback engaged.', [
                'error' => class_basename($e),
                'message' => app()->environment('production') ? 'suppressed' : $e->getMessage(),
            ]);
        }

        $stats = config('demo-bookings.stats', []);
        $brandingPayload = SafeBrandingResolver::resolveForPublic($this->brandingService);
        $settings = $brandingPayload['settings'];
        $sections = $brandingPayload['sections'] ?? collect();

        $heroSection = $sections['hero'] ?? null;
        $trustSection = $sections['trust_metrics'] ?? null;
        $featureSection = $sections['feature_cards'] ?? null;
        $popularSection = $sections['popular_routes'] ?? null;
        $whySection = $sections['why_choose_us'] ?? null;

        return view('frontend.home', [
            'popularRoutes' => $popular,
            'contacts' => config('demo-routes.contacts', []),
            'defaultDepart' => $defaultDepart,
            'defaultOrigin' => $defaultOrigin,
            'defaultDestination' => $defaultDestination,
            'client' => config('demo-client', []),
            'trustMetrics' => [
                'bookings' => (int) ($stats['total_bookings'] ?? 1847),
                'agents' => (int) ($stats['active_agents'] ?? 42),
                'volume_pkr' => (int) ($stats['monthly_revenue_pkr'] ?? 128450000),
                'api_label' => 'API-ready supplier layer',
            ],
            'homeFlightOffers' => array_slice($offers, 0, 3),
            'resultsQuery' => [
                'from' => $defaultOrigin,
                'to' => $defaultDestination,
                'depart' => $defaultDepart,
            ],
            'publicBranding' => $brandingPayload,
            'agencySettings' => $settings,
            'heroContent' => $heroSection?->content ?? [],
            'heroTitle' => $heroSection?->title,
            'heroSubtitle' => $heroSection?->subtitle,
            'trustMetricsContent' => $trustSection?->content ?? [],
            'featureCardsContent' => $featureSection?->content ?? [],
            'popularRoutesContent' => $popularSection?->content ?? [],
            'whyChooseUsContent' => $whySection?->content ?? [],
        ]);
    }
}
