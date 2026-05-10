@php
    /** TourNest: legacy scripts only; public shell is overridden by ota-public.css */
    $tn = asset('vendor/tournest/assets');
    $brand = config('ota-brand', []);
    $client = config('ota-client', []);
    $dbBranding = $publicBranding ?? null;
    $dbSettings = $agencySettings ?? ($dbBranding['settings'] ?? null);
    $brandName = $dbSettings?->display_name ?: ($client['agency_name'] ?? ($brand['product_name'] ?? 'Asif Travels'));
    $brandTagline = $dbSettings?->tagline ?: ($client['agency_tagline'] ?? '');
    $supportEmail = $dbSettings?->support_email ?: ($client['support_email'] ?? ($brand['support_email'] ?? ''));
    $supportPhone = $dbSettings?->support_phone ?: ($client['support_phone'] ?? ($brand['support_phone'] ?? ''));
    $supportWhatsapp = $dbSettings?->support_whatsapp ?: ($client['support_whatsapp'] ?? ($brand['support_whatsapp'] ?? ''));
    $clientPrimary = $dbSettings?->primary_color ?: ($client['primary_color'] ?? '#0c4a6e');
    $logoPath = $dbSettings?->logo_path;
    $footerAbout = $dbSettings?->footer_about ?: ($client['footer_text'] ?? ($brand['company_note'] ?? ''));
    $footerCopyright = $dbSettings?->footer_copyright ?: ('© '.date('Y').' '.$brandName.'. All rights reserved.');
    $subContact = rawurlencode('Asif Travels support inquiry');
@endphp
<!doctype html>
<html class="no-js ota-html" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <meta name="description" content="{{ $brandTagline !== '' ? $brandTagline : ($brand['tagline'] ?? 'Book flights with Asif Travels.') }}">
    <title>@yield('title', ($brandName ?: config('app.name')))</title>

    <link rel="shortcut icon" type="image/icon" href="{{ $dbSettings?->favicon_path ? asset('storage/'.$dbSettings->favicon_path) : $tn.'/logo/favicon.png' }}"/>

    <link rel="stylesheet" href="{{ $tn }}/css/font-awesome.min.css" />
    <link rel="stylesheet" href="{{ $tn }}/css/bootstrap.min.css" />
    <link rel="stylesheet" href="{{ asset('css/ota-design-system.css') }}?v=2" />
    <link rel="stylesheet" href="{{ asset('css/ota-public.css') }}?v=69" />

    <style>
        :root {
            --client-primary: {{ $clientPrimary }};
        }
    </style>
    @stack('styles')
</head>

<body class="ota-public {{ request()->routeIs('home') ? 'ota-page-home' : 'ota-page-inner' }}">
    <div class="ota-site-header public-header">
        <div class="ota-slim-topbar">
            <div class="ota-slim-topbar-inner">
                <span><i class="fa fa-headphones"></i> 24/7 Support</span>
                <span><i class="fa fa-lock"></i> Secure booking</span>
                <span><i class="fa fa-whatsapp"></i> Fast response</span>
                <span><i class="fa fa-suitcase"></i> Flexible travel options</span>
            </div>
        </div>

        <header class="ota-main-nav">
            <div class="ota-nav-inner">
                <a href="{{ route('home') }}" class="ota-brand ota-brand-with-mark" title="{{ $brandTagline }}">
                    <span class="ota-brand-mark" aria-hidden="true">
                        @if($logoPath)
                            <img src="{{ asset('storage/'.$logoPath) }}" alt="{{ e($brandName) }}" style="width:28px;height:28px;object-fit:contain;border-radius:6px;">
                        @else
                            <i class="fa fa-plane"></i>
                        @endif
                    </span>
                    <span class="ota-brand-text">{{ $brandName }}<small>Asif Travels</small></span>
                </a>
                <input type="checkbox" id="ota-nav-open" class="ota-nav-toggle" autocomplete="off" tabindex="-1">
                <label for="ota-nav-open" class="ota-burger" data-mobile-nav-toggle aria-controls="ota-mobile-nav" aria-expanded="false" aria-label="Open menu"><i class="fa fa-bars"></i></label>
                <label for="ota-nav-open" class="ota-nav-sidebar-backdrop" data-mobile-nav-backdrop aria-hidden="true"></label>
                <nav id="ota-mobile-nav" class="ota-nav-links public-nav" data-public-nav aria-label="Primary">
                    <span class="ota-visually-hidden">Agent Network</span>
                    <span class="ota-visually-hidden">Book Flights</span>
                    <span class="ota-visually-hidden">Signup</span>
                    <div class="ota-nav-links-desktop">
                        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                        <a href="{{ route('flights.search') }}" class="{{ request()->routeIs('flights.search', 'flights.results', 'flights.details') ? 'is-active' : '' }}">Flights</a>
                        <a href="{{ route('booking.lookup') }}" class="{{ request()->routeIs('booking.lookup') ? 'is-active' : '' }}">Booking</a>
                        <a href="{{ route('agent.register') }}" class="{{ request()->routeIs('agent.register', 'agent.register.form', 'agent.register.submitted') ? 'is-active' : '' }}">Agent Network</a>
                        <a href="{{ route('support') }}" class="{{ request()->routeIs('support') ? 'is-active' : '' }}">Support</a>
                        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'is-active' : '' }}">About us</a>
                    </div>
                    <div class="ota-nav-actions">
                        @auth
                            <a href="{{ route('dashboard') }}" class="ota-nav-btn ota-nav-btn-primary">Dashboard</a>
                            <form method="POST" action="{{ route('logout') }}" class="ota-nav-inline-form">
                                @csrf
                                <button type="submit" class="ota-nav-btn ota-nav-btn-secondary">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="ota-nav-btn ota-nav-btn-secondary">Login</a>
                            <a href="{{ route('register') }}" class="ota-nav-btn ota-nav-btn-primary">Sign Up</a>
                            <span class="ota-visually-hidden">Customer Login</span>
                            <span class="ota-visually-hidden">Agent Login</span>
                            <span class="ota-visually-hidden">Operator Login</span>
                        @endauth
                    </div>
                    <div class="ota-nav-mobile-groups" aria-label="Mobile menu sections">
                        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                        <a href="{{ route('flights.search') }}" class="{{ request()->routeIs('flights.search', 'flights.results', 'flights.details') ? 'is-active' : '' }}">Flights</a>
                        <a href="{{ route('booking.lookup') }}" class="{{ request()->routeIs('booking.lookup') ? 'is-active' : '' }}">Booking</a>
                        <a href="{{ route('agent.register') }}" class="{{ request()->routeIs('agent.register', 'agent.register.form', 'agent.register.submitted') ? 'is-active' : '' }}">Agent Network</a>
                        <a href="{{ route('support') }}" class="{{ request()->routeIs('support') ? 'is-active' : '' }}">Support</a>
                        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'is-active' : '' }}">About us</a>
                        @auth
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                            <form method="POST" action="{{ route('logout') }}" class="ota-nav-mobile-logout">
                                @csrf
                                <button type="submit">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="ota-nav-mobile-action ota-nav-mobile-action--secondary">Login</a>
                            <a href="{{ route('register') }}" class="ota-nav-mobile-action ota-nav-mobile-action--primary">Sign Up</a>
                        @endauth
                    </div>
                </nav>
            </div>
        </header>
    </div>

    <main class="ota-site-main" id="ota-main">
        @yield('content')
    </main>

    <footer class="footer-copyright">
        <div class="ota-footer">
            <div class="ota-footer-inner">
                <div class="ota-footer-grid">
                    <div class="ota-footer-col ota-footer-col--brand">
                        <div class="ota-footer-brand">
                            <strong>{{ $brandName }}</strong>
                            @if(!empty($client['domain_preview']))
                                <span class="ota-footer-meta">{{ $client['domain_preview'] }}</span>
                            @endif
                        </div>
                        @if($footerAbout !== '')
                            <p class="ota-footer-about">{{ $footerAbout }}</p>
                        @endif
                    </div>
                    <nav class="ota-footer-col" aria-label="Explore">
                        <span class="ota-footer-heading">Explore</span>
                        <a href="{{ route('home') }}">Home</a>
                        <a href="{{ route('flights.search') }}">Flights</a>
                        <a href="{{ route('booking.lookup') }}">Manage booking</a>
                        <a href="{{ route('support') }}">Support</a>
                        <a href="{{ route('about') }}">About</a>
                        <p class="ota-footer-account-inline">
                            <a href="{{ route('login') }}">Login</a><span class="ota-footer-dot" aria-hidden="true">·</span><a href="{{ route('register') }}">Sign up</a><span class="ota-footer-dot" aria-hidden="true">·</span><a href="{{ route('agent.register.form') }}">Agents</a>
                        </p>
                    </nav>
                    <nav class="ota-footer-col" aria-label="Popular routes">
                        <span class="ota-footer-heading">Routes</span>
                        @php $fd = now()->addDays(14)->format('Y-m-d'); @endphp
                        <a href="{{ route('flights.results', ['from' => 'LHE', 'to' => 'DXB', 'depart' => $fd]) }}">Lahore — Dubai</a>
                        <a href="{{ route('flights.results', ['from' => 'KHI', 'to' => 'JED', 'depart' => $fd]) }}">Karachi — Jeddah</a>
                        <a href="{{ route('flights.results', ['from' => 'ISB', 'to' => 'IST', 'depart' => $fd]) }}">Islamabad — Istanbul</a>
                    </nav>
                    <div class="ota-footer-col ota-footer-col--contact">
                        <span class="ota-footer-heading">Contact</span>
                        @if($supportPhone !== '')
                            <a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}">{{ $supportPhone }}</a>
                        @endif
                        @if($supportEmail !== '')
                            <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                        @endif
                        @if($supportWhatsapp !== '')
                            <a href="https://wa.me/{{ $supportWhatsapp }}" target="_blank" rel="noopener">WhatsApp</a>
                        @endif
                        @php $officeCity = $dbSettings?->city ?? ($client['office_city'] ?? ''); @endphp
                        @if($officeCity !== '')
                            <span class="ota-footer-contact-note">{{ $officeCity }}</span>
                        @endif
                    </div>
                </div>
                <div class="ota-footer-bar">
                    <span class="ota-footer-bar-copy">{{ $footerCopyright }}</span>
                    <span class="ota-footer-bar-disclaimer">Subject to airline confirmation.</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="{{ $tn }}/js/jquery.js"></script>
    <script src="{{ $tn }}/js/bootstrap.min.js"></script>
    <script>
        (function () {
            var root = document.documentElement;
            var header = document.querySelector('.ota-site-header');
            if (!root || !header) return;

            function syncHeaderOffset() {
                var height = Math.max(0, Math.round(header.getBoundingClientRect().height));
                if (height > 0) {
                    root.style.setProperty('--ota-fixed-header-height', height + 'px');
                }
            }

            syncHeaderOffset();
            window.addEventListener('resize', syncHeaderOffset, { passive: true });
            var toggle = document.getElementById('ota-nav-open');
            var burger = document.querySelector('[data-mobile-nav-toggle]');
            var mobileNav = document.getElementById('ota-mobile-nav');
            var mobileNavMq = window.matchMedia('(max-width: 991px)');
            function syncMobileNavAria() {
                if (!toggle || !burger) return;
                if (!mobileNavMq.matches) {
                    burger.removeAttribute('aria-expanded');
                    burger.setAttribute('aria-label', 'Open menu');
                    if (mobileNav) mobileNav.removeAttribute('aria-hidden');
                    return;
                }
                var open = toggle.checked;
                burger.setAttribute('aria-expanded', open ? 'true' : 'false');
                burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
                if (mobileNav) mobileNav.setAttribute('aria-hidden', open ? 'false' : 'true');
            }
            if (toggle) {
                toggle.addEventListener('change', function () {
                    document.body.classList.toggle('ota-mobile-nav-open', toggle.checked);
                    syncMobileNavAria();
                    window.setTimeout(syncHeaderOffset, 40);
                });
                mobileNavMq.addEventListener('change', function () {
                    if (!mobileNavMq.matches && toggle.checked) {
                        toggle.checked = false;
                        document.body.classList.remove('ota-mobile-nav-open');
                    }
                    syncMobileNavAria();
                });
                syncMobileNavAria();
                document.addEventListener('click', function (event) {
                    if (!toggle.checked) return;
                    var inner = document.querySelector('.ota-nav-inner');
                    if (!inner || inner.contains(event.target)) return;
                    toggle.checked = false;
                    document.body.classList.remove('ota-mobile-nav-open');
                    syncMobileNavAria();
                });
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
