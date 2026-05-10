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
    <link rel="stylesheet" href="{{ asset('css/ota-design-system.css') }}?v=7">
    <link rel="stylesheet" href="{{ asset('css/ota-public.css') }}?v=32">
</head>
@php($authCardClass = trim($__env->yieldContent('auth_card_class')))
<body class="ota-auth">
    <main class="auth-shell auth-shell--premium">
        <section class="auth-card {{ $authCardClass }}" data-auth-premium-layout>
            <p class="ota-visually-hidden">Welcome to Asif Travels</p>
            @yield('content')
            <p class="ota-auth-support">
                Need Help? Contact <a class="ota-auth-link" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
            </p>
        </section>
    </main>
    @stack('scripts')
</body>
</html>
