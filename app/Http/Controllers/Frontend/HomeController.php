<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Agencies\AgencyBrandingService;
use App\Support\Branding\SafeBrandingResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected AgencyBrandingService $brandingService,
    ) {}

    public function index(Request $request): View
    {
        $popular = config('ota-routes.popular', []);

        $stats = config('ota-bookings.stats', []);
        $brandingPayload = SafeBrandingResolver::resolveForPublic($this->brandingService);
        $settings = $brandingPayload['settings'];
        $sections = $brandingPayload['sections'] ?? collect();

        $heroSection = $sections['hero'] ?? null;
        $trustSection = $sections['trust_metrics'] ?? null;
        $featureSection = $sections['feature_cards'] ?? null;
        $popularSection = $sections['popular_routes'] ?? null;
        $whySection = $sections['why_choose_us'] ?? null;

        $recentFarePayload = $request->session()->get('home_recent_fares', []);
        $recentOffers = is_array($recentFarePayload['offers'] ?? null) ? $recentFarePayload['offers'] : [];
        $recentCriteria = is_array($recentFarePayload['criteria'] ?? null) ? $recentFarePayload['criteria'] : [];
        $sampleFares = array_slice((array) config('ota-flights.offers', []), 0, 3);

        return view('frontend.home', [
            'popularRoutes' => $popular,
            'contacts' => config('ota-routes.contacts', []),
            'defaultDepart' => '',
            'defaultOrigin' => '',
            'defaultDestination' => '',
            'defaultReturnDate' => '',
            'defaultTripType' => 'one_way',
            'minDate' => now()->format('Y-m-d'),
            'client' => config('ota-client', []),
            'trustMetrics' => [
                'bookings' => (int) ($stats['total_bookings'] ?? 1847),
                'agents' => (int) ($stats['active_agents'] ?? 42),
                'volume_pkr' => (int) ($stats['monthly_revenue_pkr'] ?? 128450000),
                'api_label' => 'API-ready supplier layer',
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
            'recentFareOffers' => $recentOffers,
            'recentFareCriteria' => $recentCriteria,
            'sampleFareOffers' => $sampleFares,
        ]);
    }
}
