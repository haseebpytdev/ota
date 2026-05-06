@extends('layouts.frontend')

@section('title', ($client['agency_name'] ?? config('app.name')).' — Flights')

@section('content')
    @php
        $b = config('ota-brand', []);
        $client = $client ?? config('ota-client', []);
        $agencySettings = $agencySettings ?? null;
        $heroContent = $heroContent ?? [];
        $heroTitle = $heroTitle ?: ($b['homepage_headline'] ?? 'Book Flights With Confidence');
        $heroSubtitle = $heroSubtitle ?: ($b['homepage_subheadline'] ?? '');
        $safeHeroCtaUrl = filter_var((string) ($agencySettings?->header_cta_url ?? route('flights.search')), FILTER_VALIDATE_URL) ?: route('flights.search');
        $safeHeroImage = $agencySettings?->hero_image_path ? asset('storage/'.$agencySettings->hero_image_path) : null;
    @endphp
    <section class="ota-hero">
        <div class="ota-hero-inner">
            <div class="ota-hero-grid">
                <div>
                    <span class="ota-hero-badge">{{ $agencySettings?->display_name ?? ($client['agency_name'] ?? 'Asif Travels') }}</span>
                    <h1>{{ $heroTitle }}</h1>
                    <p class="ota-hero-lead">{{ $heroSubtitle }}</p>
                    <div class="ota-hero-actions">
                        <a href="#ota-flight-search" class="ota-btn-white"><i class="fa fa-search"></i> Book Flights</a>
                        <a href="{{ route('agent.register') }}" class="ota-btn-ghost" title="Agent Network"><i class="fa fa-users"></i> Agent Network</a>
                    </div>
                    <p class="ota-hero-help-hint" id="support">
                        <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>
                        Need booking help? Visit <a href="{{ route('support') }}">Customer Support</a> for email and WhatsApp assistance.
                    </p>
                </div>
                <div>
                    @if($safeHeroImage)
                        <img src="{{ $safeHeroImage }}" alt="Hero image" style="width:100%;max-height:220px;object-fit:cover;border-radius:10px;margin-bottom:10px;">
                    @endif
                    @include('frontend.partials.ota-flight-widget', [
                        'variant' => 'hero',
                        'show_intro' => true,
                        'defaultDepart' => $defaultDepart ?? '',
                        'defaultOrigin' => $defaultOrigin ?? '',
                        'defaultDestination' => $defaultDestination ?? '',
                        'defaultReturnDate' => $defaultReturnDate ?? '',
                        'defaultTripType' => $defaultTripType ?? 'one_way',
                        'minDate' => $minDate ?? now()->format('Y-m-d'),
                    ])
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.ota-home-trust-metrics', ['trustMetricsContent' => $trustMetricsContent ?? []])
    @include('frontend.partials.ota-home-fares-preview')
    @include('frontend.partials.ota-home-admin-preview')
    @include('frontend.partials.ota-landing-features', ['featureCardsContent' => $featureCardsContent ?? []])
    @include('frontend.partials.ota-popular-routes', ['popularRoutes' => $popularRoutes, 'defaultDepart' => $defaultDepart, 'popularRoutesContent' => $popularRoutesContent ?? []])
    @include('frontend.partials.ota-landing-why', ['whyChooseUsContent' => $whyChooseUsContent ?? []])
@endsection
