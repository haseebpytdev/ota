@php
    $brand = config('ota-brand', []);
    $client = config('ota-client', []);
    $safeBranding = \App\Support\Branding\SafeBrandingResolver::resolveForPublic(app(\App\Services\Agencies\AgencyBrandingService::class));
    $settings = $safeBranding['settings'] ?? null;
    $brandName = $settings?->display_name ?: ($client['agency_name'] ?? ($brand['product_name'] ?? 'Asif Travels'));
    $tagline = $settings?->tagline ?: ($client['agency_tagline'] ?? ($brand['tagline'] ?? 'Travel booking made simple.'));
    $supportEmail = $settings?->support_email ?: ($client['support_email'] ?? ($brand['support_email'] ?? 'support@haseebasif.com'));
    $supportPhone = $settings?->support_phone ?: ($client['support_phone'] ?? ($brand['support_phone'] ?? '+92 300 7654321'));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account access') - {{ $brandName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/ota-design-system.css') }}?v=1">
</head>
<body class="ota-auth">
    <main class="ota-auth-shell">
        <section class="ota-auth-card ota-container-form">
            <aside class="ota-auth-brand">
                <a href="{{ route('home') }}" class="ota-auth-link" style="color:#fff;text-decoration:none;font-weight:800;">{{ $brandName }}</a>
                <h1>Welcome to {{ $brandName }}</h1>
                <p>{{ $tagline }}</p>
                <ul class="ota-auth-brand-list">
                    <li>Search and request flights</li>
                    <li>Track booking status</li>
                    <li>Download receipts and travel documents</li>
                </ul>
            </aside>
            <div class="ota-auth-form-wrap">
                @yield('content')
                <p class="ota-auth-support">
                    Need help? Contact <a class="ota-auth-link" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> or return to
                    <a class="ota-auth-link" href="{{ route('home') }}">homepage</a>.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
