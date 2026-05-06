@extends('layouts.frontend')

@section('title', 'Flight search')

@section('content')
    <section class="ota-section ota-routes-section ota-flight-search-wrap">
        <div class="ota-container">
            <header class="ota-section-head">
                <p class="ota-section-kicker">Flights</p>
                <h1 class="ota-section-title">Book your next flight</h1>
                <p class="ota-section-desc">Search routes, compare fares, and continue to booking review with Asif Travels support.</p>
            </header>
            @include('frontend.partials.ota-flight-widget', [
                'defaultDepart' => $defaults['depart'],
                'defaultOrigin' => $defaults['origin'],
                'defaultDestination' => $defaults['destination'],
            ])
        </div>
    </section>
@endsection
