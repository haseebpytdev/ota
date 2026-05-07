@php
    /** @var array<int, array<string, string>> $popularRoutes */
    $popularRoutes = $popularRoutes;
    if (is_array(($popularRoutesContent['routes'] ?? null)) && count($popularRoutesContent['routes']) > 0) {
        $popularRoutes = array_map(function ($item): array {
            return [
                'from' => (string) ($item['from'] ?? 'LHE'),
                'to' => (string) ($item['to'] ?? 'DXB'),
                'label' => (string) ($item['label'] ?? (($item['from'] ?? 'LHE').' → '.($item['to'] ?? 'DXB'))),
            ];
        }, $popularRoutesContent['routes']);
    }
    $depart =
        (isset($defaultDepart) && $defaultDepart !== '')
            ? $defaultDepart
            : now()->addDays(14)->format('Y-m-d');
@endphp
<section class="ota-section ota-routes-section" id="routes">
    <div class="ota-container">
        <header class="ota-section-head">
            <p class="ota-section-kicker">Routes</p>
            <h2 class="ota-section-title">Popular corridors</h2>
            <p class="ota-section-desc">Quick links to search popular routes — final fare shown in PKR after you choose dates.</p>
        </header>
        <div class="ota-routes-grid">
            @foreach ($popularRoutes as $route)
                <a href="{{ route('flights.results', ['trip_type' => 'one_way', 'from' => $route['from'], 'to' => $route['to'], 'depart' => $depart, 'cabin' => 'economy', 'adults' => 1, 'children' => 0, 'infants' => 0]) }}" class="ota-route-card">
                    <strong>{{ $route['label'] }}</strong>
                    <span>{{ $route['from'] }} → {{ $route['to'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
