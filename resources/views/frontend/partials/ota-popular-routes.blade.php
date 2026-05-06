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
@endphp
<section class="ota-section ota-routes-section" id="routes">
    <div class="ota-container">
        <header class="ota-section-head">
            <p class="ota-section-kicker">Routes</p>
            <h2 class="ota-section-title">Popular corridors</h2>
            <p class="ota-section-desc">Jump straight into current fare results using the same booking flow.</p>
        </header>
        <div class="ota-routes-grid">
            @foreach ($popularRoutes as $route)
                <a href="{{ route('flights.results', ['from' => $route['from'], 'to' => $route['to'], 'depart' => $defaultDepart]) }}" class="ota-route-card">
                    <strong>{{ $route['label'] }}</strong>
                    <span>{{ $route['from'] }} → {{ $route['to'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
