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
    <link rel="stylesheet" href="{{ asset('css/ota-design-system.css') }}?v=1" />
    <link rel="stylesheet" href="{{ asset('css/ota-public.css') }}?v=16" />

    <style>
        :root {
            --client-primary: {{ $clientPrimary }};
        }
    </style>
    @stack('styles')
</head>

<body class="ota-public">
    <div class="ota-site-header">
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
                <input type="checkbox" id="ota-nav-open" class="ota-nav-toggle" autocomplete="off">
                <label for="ota-nav-open" class="ota-burger" aria-label="Open menu"><i class="fa fa-bars"></i></label>
                <nav class="ota-nav-links" aria-label="Primary">
                    <div class="ota-nav-links-desktop">
                        <a href="{{ route('home') }}">Home</a>
                        <a href="{{ route('flights.search') }}">Flights</a>
                        <a href="{{ route('agent.register') }}">Agent Network</a>
                        <a href="{{ route('support') }}">Support</a>
                        <a href="{{ route('contact') }}">Contact</a>
                    </div>
                    <div class="ota-nav-dropdown">
                        <button type="button" class="ota-nav-dropdown-toggle">Login <i class="fa fa-angle-down" aria-hidden="true"></i></button>
                        <div class="ota-nav-dropdown-menu">
                            <a href="{{ route('login') }}">
                                <span class="ota-nav-dropdown-title">Customer Login</span>
                                <span class="ota-nav-dropdown-meta">Manage trips and documents</span>
                            </a>
                            <a href="{{ route('login', ['type' => 'agent']) }}">
                                <span class="ota-nav-dropdown-title">Agent Login</span>
                                <span class="ota-nav-dropdown-meta">Manage booking requests and commissions</span>
                            </a>
                            <a href="{{ route('login', ['type' => 'operator']) }}">
                                <span class="ota-nav-dropdown-title">Operator Login</span>
                                <span class="ota-nav-dropdown-meta">Operations and back-office access</span>
                            </a>
                        </div>
                    </div>
                    <div class="ota-nav-dropdown">
                        <button type="button" class="ota-nav-dropdown-toggle">Signup <i class="fa fa-angle-down" aria-hidden="true"></i></button>
                        <div class="ota-nav-dropdown-menu">
                            <a href="{{ route('register') }}">
                                <span class="ota-nav-dropdown-title">Customer Signup</span>
                                <span class="ota-nav-dropdown-meta">Book and manage your trips</span>
                            </a>
                            <a href="{{ route('agent.register.form') }}">
                                <span class="ota-nav-dropdown-title">Agent Registration</span>
                                <span class="ota-nav-dropdown-meta">Apply for partner access</span>
                            </a>
                        </div>
                    </div>
                    <div class="ota-nav-mobile-groups" aria-label="Mobile menu sections">
                        <div class="ota-mobile-group">
                            <div class="ota-mobile-group-title">Book</div>
                            <a href="{{ route('flights.search') }}">Search Flights</a>
                            <a href="{{ route('lookup-booking.form') }}">Lookup Booking</a>
                        </div>
                        <div class="ota-mobile-group">
                            <div class="ota-mobile-group-title">Accounts</div>
                            <a href="{{ route('login') }}">Customer Login</a>
                            <a href="{{ route('register') }}">Customer Signup</a>
                            <a href="{{ route('login', ['type' => 'agent']) }}">Agent Login</a>
                            <a href="{{ route('agent.register.form') }}">Agent Registration</a>
                            <a href="{{ route('login', ['type' => 'operator']) }}">Operator Login</a>
                        </div>
                        <div class="ota-mobile-group">
                            <div class="ota-mobile-group-title">Help</div>
                            <a href="{{ route('support') }}">Support</a>
                            <a href="{{ route('contact') }}">Contact</a>
                        </div>
                    </div>
                    <a href="{{ route('flights.search') }}" class="ota-nav-cta ota-nav-cta-inmenu">Book Flights</a>
                </nav>
                @unless (request()->routeIs('flights.search'))
                    <a href="{{ route('flights.search') }}" class="ota-nav-cta ota-nav-cta-floating">Book Flights</a>
                @endunless
            </div>
        </header>
    </div>

    <main class="ota-site-main" id="ota-main">
        @yield('content')
    </main>

    <footer class="footer-copyright">
        <div class="ota-footer">
            <div class="ota-footer-grid">
                <div>
                    <h4>Brand</h4>
                    <div class="ota-footer-brand">
                        <strong>{{ $brandName }}</strong>
                        <span style="color:#94a3b8;font-size:0.8rem;">{{ $client['domain_preview'] ?? '' }}</span>
                    </div>
                    <p style="margin:0.5rem 0 0;font-size:0.78rem;line-height:1.45;color:#64748b;">{{ $footerAbout }}</p>
                    <p class="ota-powered-by">Flight booking support, travel assistance, and customer care from {{ $brandName }}.</p>
                </div>
                <div>
                    <h4>Quick links</h4>
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('flights.search') }}">Flights</a>
                    <a href="{{ route('support') }}">Support</a>
                    <a href="{{ route('contact') }}">Contact</a>
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Customer Signup</a>
                    <a href="{{ route('agent.register.form') }}">Agent Registration</a>
                </div>
                <div>
                    <h4>Popular routes</h4>
                    @php $fd = now()->addDays(14)->format('Y-m-d'); @endphp
                    <a href="{{ route('flights.results', ['from' => 'LHE', 'to' => 'DXB', 'depart' => $fd]) }}">Lahore → Dubai</a>
                    <a href="{{ route('flights.results', ['from' => 'KHI', 'to' => 'JED', 'depart' => $fd]) }}">Karachi → Jeddah</a>
                    <a href="{{ route('flights.results', ['from' => 'ISB', 'to' => 'IST', 'depart' => $fd]) }}">Islamabad → Istanbul</a>
                </div>
                <div>
                    <h4>Contact</h4>
                    <a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}">{{ $supportPhone }}</a>
                    <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                    <a href="https://wa.me/{{ $supportWhatsapp }}" target="_blank" rel="noopener">WhatsApp</a>
                    <span style="display:block;margin-top:0.35rem;color:#64748b;">{{ $dbSettings?->city ?? ($client['office_city'] ?? '') }}</span>
                </div>
            </div>
            <div class="ota-footer-bar">
                <span>{{ $footerCopyright }}</span>
                <span>Flight availability is subject to airline confirmation.</span>
            </div>
        </div>
    </footer>

    <script src="{{ $tn }}/js/jquery.js"></script>
    <script src="{{ $tn }}/js/bootstrap.min.js"></script>
    <script>
        (function () {
            var nav = document.querySelector('.ota-nav-links');
            if (!nav) return;
            var dropdowns = nav.querySelectorAll('.ota-nav-dropdown');
            dropdowns.forEach(function (dropdown) {
                var toggle = dropdown.querySelector('.ota-nav-dropdown-toggle');
                if (!toggle) return;
                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    var isOpen = dropdown.classList.contains('is-open');
                    dropdowns.forEach(function (item) { item.classList.remove('is-open'); });
                    if (!isOpen) dropdown.classList.add('is-open');
                });
            });
            document.addEventListener('click', function (event) {
                if (!nav.contains(event.target)) {
                    dropdowns.forEach(function (item) { item.classList.remove('is-open'); });
                }
            });
        })();

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
            if (toggle) {
                toggle.addEventListener('change', function () {
                    window.setTimeout(syncHeaderOffset, 40);
                });
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
