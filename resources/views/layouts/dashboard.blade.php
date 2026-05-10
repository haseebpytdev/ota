@php
    $tabler = asset('vendor/tabler');
    $dashBrand = config('ota-brand', []);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ $dashBrand['product_name'] ?? config('app.name') }}</title>

    {{-- Tabler core (from @tabler/core dist, copied to public/vendor/tabler) --}}
    <link href="{{ $tabler }}/css/tabler.min.css" rel="stylesheet"/>
    <link href="{{ $tabler }}/css/tabler-flags.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/ota-design-system.css') }}?v=1" />
    <style>
        /* Slightly larger body copy than Tabler defaults */
        body { font-size: 16px; line-height: 1.55; }
        .text-muted, small { font-size: 0.9rem; }
    </style>

    {{-- Icons: vendorize into public/vendor/tabler/icons by copying @tabler/icons-webfont dist when offline --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.40.0/dist/tabler-icons.min.css"/>

    <style>
        /* Consistent disabled controls */
        .page .btn:disabled,
        .page .btn.disabled,
        .page fieldset:disabled .btn {
            opacity: 1;
            background: #e2e8f0 !important;
            border-color: #cbd5e1 !important;
            color: #475569 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
        }
        .page .form-control:disabled,
        .page .form-select:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .ops-admin-banner {
            border-left: 4px solid var(--tblr-warning, #f59e0b);
        }
        /* Operator console — branded polish */
        .ota-sidebar-refined .navbar-brand {
            padding-bottom: 0.75rem;
            margin-bottom: 0.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .ota-sidebar-refined .nav-link {
            border-radius: 8px;
            margin: 0.1rem 0.35rem;
            padding: 0.5rem 0.65rem !important;
            font-size: 0.9375rem;
        }
        .ota-sidebar-refined .nav-link.active {
            background: rgba(37, 99, 235, 0.22) !important;
            color: #fff !important;
        }
        .ota-sidebar-refined .nav-link:hover:not(.disabled) {
            background: rgba(255, 255, 255, 0.06);
        }
        .ota-submenu {
            margin: -0.05rem 0 0.35rem 2.35rem;
            padding: 0;
            list-style: none;
        }
        .ota-submenu .nav-link {
            margin: 0.05rem 0;
            padding: 0.32rem 0.55rem !important;
            font-size: 0.82rem;
            border-radius: 7px;
            color: rgba(255, 255, 255, 0.72);
        }
        .ota-submenu .nav-link.active {
            background: rgba(14, 165, 233, 0.2) !important;
            color: #fff !important;
        }
        .ota-sidebar-refined .nav-link[data-bs-toggle="collapse"] {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .ota-sidebar-refined .nav-link[data-bs-toggle="collapse"] .nav-link-title {
            flex: 1;
        }
        .ota-nav-caret {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            font-size: 0.95rem;
            line-height: 1;
            opacity: 0.7;
            transition: transform 0.18s ease;
        }
        .ota-sidebar-refined .nav-link[aria-expanded="true"] .ota-nav-caret {
            transform: rotate(180deg);
        }
        .ota-sidebar-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.45rem;
            border-radius: 999px;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            background: rgba(37, 99, 235, 0.22);
            color: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.14);
            flex-shrink: 0;
        }
        .ota-sidebar-section {
            padding: 0.65rem 0.85rem 0.35rem;
            margin-top: 0.5rem;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.64);
        }
        .ota-admin-welcome {
            border: 1px solid rgba(37, 99, 235, 0.2);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.06) 0%, rgba(14, 165, 233, 0.05) 100%);
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        }
        .ota-admin-welcome--compact {
            box-shadow: 0 2px 14px rgba(15, 23, 42, 0.05);
        }
        .ota-admin-welcome-body {
            padding: 22px 24px !important;
        }
        .ota-admin-welcome .card-title,
        .ota-admin-welcome-title {
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .ota-admin-welcome-title {
            font-size: 1.05rem;
            line-height: 1.3;
        }
        .ota-monthly-overview-head {
            letter-spacing: -0.01em;
            color: var(--tblr-body-color, #1e293b);
        }
        .ota-welcome-avatar {
            width: 2.65rem !important;
            height: 2.65rem !important;
            min-width: 2.65rem !important;
        }
        .ota-admin-welcome--compact .ota-welcome-avatar {
            width: 2.35rem !important;
            height: 2.35rem !important;
            min-width: 2.35rem !important;
        }
        .ota-welcome-avatar .ti {
            font-size: 1.35rem !important;
        }
        .ota-admin-welcome--compact .ota-welcome-avatar .ti {
            font-size: 1.15rem !important;
        }
        .ota-kpi-card {
            border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, 0.16));
            border-top: 3px solid #2563eb;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
        }
        .ota-kpi-accent-amber {
            border-top-color: #f59e0b !important;
        }
        .ota-kpi-accent-emerald {
            border-top-color: #10b981 !important;
        }
        .ota-kpi-accent-violet {
            border-top-color: #8b5cf6 !important;
        }
        .ota-kpi-card .text-secondary {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .ota-kpi-card .h2 {
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .ota-admin-table thead th {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
            color: var(--tblr-secondary, #62748e);
            border-bottom-width: 2px;
        }
        .ota-admin-table tbody tr:hover {
            background: rgba(37, 99, 235, 0.04);
        }
        .ota-admin-quick .ota-quick-action-card {
            border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, 0.16));
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            cursor: pointer;
        }
        .ota-quick-action-link {
            display: block;
            outline: none;
        }
        .ota-quick-action-link:focus-visible .ota-quick-action-card {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.35);
        }
        .ota-admin-quick a:hover .ota-quick-action-card {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14);
            border-color: rgba(37, 99, 235, 0.45);
        }
        .ota-admin-quick a:active .ota-quick-action-card {
            transform: translateY(-1px);
        }
        .ota-admin-quick .ota-quick-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(14, 165, 233, 0.1));
            color: #2563eb;
        }
        .ota-recent-card-head {
            padding-top: 1.1rem !important;
            padding-bottom: 0.85rem !important;
            padding-left: 1.25rem;
            padding-right: 1.25rem;
        }
        .ota-recent-head {
            margin: 0;
            padding: 0;
        }
        .ota-recent-head-title {
            display: block;
            font-size: 1.1rem;
            font-weight: 800;
            margin: 0 0 0.5rem;
            letter-spacing: -0.02em;
            color: var(--tblr-body-color, #1e293b);
        }
        .ota-recent-head-sub {
            line-height: 1.5;
            margin: 0;
        }
        .ota-recent-head-sub-line {
            display: inline-block;
            margin-right: 0.35rem;
        }
        .ota-recent-source {
            display: inline-block;
            font-weight: 800;
            font-size: 0.82rem;
            letter-spacing: 0.02em;
            color: var(--tblr-body-color, #1e293b);
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.18);
            border-radius: 6px;
            padding: 0.12rem 0.45rem;
        }
        .ota-recent-bookings-card .table-responsive {
            border-top: 1px solid var(--tblr-border-color, rgba(98, 105, 118, 0.12));
        }
        .ota-recent-code {
            font-size: 0.8rem;
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
            background: rgba(37, 99, 235, 0.08);
            color: inherit;
        }
        .ota-supplier-status {
            display: inline-flex;
            align-items: center;
            padding: 0.28rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
            background: #f1f5f9;
            color: #334155;
        }
        .ota-supplier-status--unknown {
            background: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }
        .ota-supplier-status--pending {
            background: #fef3c7;
            color: #78350f;
            border-color: #fcd34d;
        }
        .ota-supplier-status--not-configured {
            background: #f1f5f9;
            color: #475569;
            border-color: #e2e8f0;
        }
        .ota-supplier-status--live {
            background: #d1fae5;
            color: #065f46;
            border-color: #6ee7b7;
        }
        .ota-supplier-status--configured {
            background: #e0f2fe;
            color: #075985;
            border-color: #7dd3fc;
        }
        .ota-supplier-status--connected {
            background: #ecfdf5;
            color: #14532d;
            border-color: #6ee7b7;
        }
        .ota-bstat {
            display: inline-flex;
            align-items: center;
            padding: 0.28rem 0.6rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            color: #334155;
        }
        .ota-bstat--confirmed {
            background: #dbeafe;
            color: #1e3a8a;
            border-color: #3b82f6;
        }
        .ota-bstat--pending {
            background: #fef9c3;
            color: #78350f;
            border-color: #eab308;
        }
        .ota-bstat--ticketed {
            background: #d1fae5;
            color: #14532d;
            border-color: #22c55e;
        }
        .ota-bstat--cancelled {
            background: #fee2e2;
            color: #7f1d1d;
            border-color: #ef4444;
        }
        .ota-bstat--muted {
            background: #f1f5f9;
            color: #475569;
            border-color: #e2e8f0;
        }
        /* Operations command banner */
        .ota-command-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0c4a6e 100%);
            border: 1px solid rgba(15, 23, 42, 0.2);
            border-radius: 14px;
            color: #e2e8f0;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
            padding: 22px 26px;
        }
        .ota-command-banner .ota-cb-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.65);
            font-weight: 700;
            margin: 0 0 0.35rem;
        }
        .ota-command-banner .ota-cb-headline {
            font-size: 1.45rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 0.5rem;
            letter-spacing: -0.02em;
        }
        .ota-command-banner .ota-cb-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 1rem;
            color: rgba(226, 232, 240, 0.92);
            font-size: 0.92rem;
            margin: 0;
        }
        .ota-command-banner .ota-cb-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 999px;
            padding: 0.2rem 0.7rem;
            font-size: 0.82rem;
        }
        .ota-command-banner .ota-cb-chip i { font-size: 0.95rem; }
        .ota-command-banner .ota-cb-chip--good { background: rgba(16, 185, 129, 0.18); border-color: rgba(110, 231, 183, 0.35); color: #ecfdf5; }
        .ota-command-banner .ota-cb-chip--warn { background: rgba(245, 158, 11, 0.18); border-color: rgba(252, 211, 77, 0.4); color: #fffbeb; }
        .ota-command-banner .ota-cb-chip--alert { background: rgba(239, 68, 68, 0.18); border-color: rgba(252, 165, 165, 0.4); color: #fef2f2; }
        .ota-command-banner .btn {
            font-weight: 600;
        }
        .ota-command-banner .ota-cb-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        /* Operational KPI cards */
        .ota-op-kpi {
            border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, 0.16));
            border-radius: 12px;
            background: #fff;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            display: block;
            text-decoration: none;
            color: inherit;
            height: 100%;
        }
        .ota-op-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.1);
            color: inherit;
            text-decoration: none;
        }
        .ota-op-kpi .card-body { padding: 1rem 1.1rem; }
        .ota-op-kpi-icon {
            width: 2.25rem; height: 2.25rem; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.15rem; margin-bottom: 0.5rem;
        }
        .ota-op-kpi-label {
            font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em;
            font-weight: 700; color: var(--tblr-secondary, #62748e);
        }
        .ota-op-kpi-count {
            font-size: 1.65rem; font-weight: 800; line-height: 1.1;
            letter-spacing: -0.02em; color: var(--tblr-body-color, #1e293b);
        }
        .ota-op-kpi-helper { font-size: 0.78rem; color: var(--tblr-secondary, #62748e); }
        .ota-op-kpi--warning .ota-op-kpi-icon { background: rgba(245, 158, 11, 0.15); color: #b45309; }
        .ota-op-kpi--info    .ota-op-kpi-icon { background: rgba(14, 165, 233, 0.15); color: #0369a1; }
        .ota-op-kpi--primary .ota-op-kpi-icon { background: rgba(37, 99, 235, 0.15); color: #1d4ed8; }
        .ota-op-kpi--success .ota-op-kpi-icon { background: rgba(16, 185, 129, 0.15); color: #047857; }
        .ota-op-kpi--danger  .ota-op-kpi-icon { background: rgba(239, 68, 68, 0.15); color: #b91c1c; }
        .ota-op-kpi--muted   .ota-op-kpi-icon { background: rgba(100, 116, 139, 0.15); color: #475569; }
        /* Needs attention list */
        .ota-attn-card .list-group-item {
            border-left: 0; border-right: 0; border-radius: 0;
        }
        .ota-attn-card .list-group-item:first-child { border-top: 0; }
        .ota-attn-row {
            display: flex; align-items: center; gap: 0.85rem;
            padding: 0.55rem 0;
        }
        .ota-attn-count {
            min-width: 2.2rem; text-align: center;
            font-weight: 800; font-size: 1rem;
            background: rgba(37, 99, 235, 0.1); color: #1e3a8a;
            border-radius: 6px; padding: 0.2rem 0.4rem;
        }
        .ota-attn-count--zero { background: #f1f5f9; color: #64748b; }
        .ota-attn-label { font-weight: 600; color: var(--tblr-body-color, #1e293b); }
        .ota-attn-helper { font-size: 0.78rem; color: var(--tblr-secondary, #62748e); }
        /* Provider health pills */
        .ota-prov-status {
            display: inline-flex; align-items: center;
            padding: 0.22rem 0.55rem; border-radius: 999px;
            font-size: 0.7rem; font-weight: 700;
            letter-spacing: 0.03em; border: 1px solid #e2e8f0;
        }
        .ota-prov-status--connected { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
        .ota-prov-status--disabled  { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .ota-prov-status--error     { background: #fee2e2; color: #7f1d1d; border-color: #fca5a5; }
        .ota-prov-status--not_configured { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
        .ota-prov-card {
            border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, 0.16));
            border-radius: 10px;
            padding: 0.85rem 1rem;
            background: #fff;
            display: flex; align-items: center; gap: 0.85rem;
        }
        .ota-prov-card + .ota-prov-card { margin-top: 0.5rem; }
        .ota-prov-card .ota-prov-meta { font-size: 0.78rem; color: var(--tblr-secondary, #62748e); }
        .ota-prov-card .ota-prov-error {
            font-size: 0.74rem; color: #b91c1c; margin-top: 0.15rem; word-break: break-word;
        }
    </style>
    @stack('styles')
</head>
<body>
{{-- Theme script (Tabler color mode); keep before body content per Tabler docs --}}
<script src="{{ $tabler }}/js/tabler-theme.min.js"></script>

<div class="page">
    {{-- Dynamic: swap sidebar per role (admin vs staff) and highlight active route --}}
    <aside class="navbar navbar-vertical navbar-expand-lg ota-sidebar-refined" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="{{ route('admin.dashboard') }}" class="d-block lh-sm">
                    <span class="d-block">{{ $dashBrand['product_name'] ?? config('app.name') }}</span>
                    <span class="d-block fw-normal text-secondary" style="font-size: 0.68rem;">Operator Console</span>
                </a>
            </h1>
            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav pt-lg-3">
                    <li class="nav-item">
                        <div class="ota-sidebar-section"><span>Operations</span></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-dashboard"></i></span>
                            <span class="nav-link-title">Dashboard</span>
                        </a>
                    </li>
                    @php
                        $bookingsActive = request()->routeIs('admin.bookings');
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link {{ $bookingsActive ? 'active' : '' }}"
                           href="#sidebar-bookings-submenu"
                           data-bs-toggle="collapse"
                           role="button"
                           aria-expanded="{{ $bookingsActive ? 'true' : 'false' }}"
                           aria-controls="sidebar-bookings-submenu">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-ticket"></i></span>
                            <span class="nav-link-title">Bookings</span>
                            <span class="ota-nav-caret"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $bookingsActive ? 'show' : '' }}" id="sidebar-bookings-submenu">
                            <ul class="ota-submenu">
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue', 'all') === 'all' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'all']) }}">All bookings</a>
                                </li>
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue') === 'needs_action' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'needs_action']) }}">Needs action</a>
                                </li>
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue') === 'payment_review' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'payment_review']) }}">Payment review</a>
                                </li>
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue') === 'supplier_pnr' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'supplier_pnr']) }}">Supplier / PNR</a>
                                </li>
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue') === 'ticketing' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'ticketing']) }}">Ticketing</a>
                                </li>
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue') === 'cancellations' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'cancellations']) }}">Cancellations</a>
                                </li>
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue') === 'refunds' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'refunds']) }}">Refunds</a>
                                </li>
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue') === 'invoices' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'invoices']) }}">Invoices</a>
                                </li>
                                <li>
                                    <a class="nav-link {{ $bookingsActive && request()->query('queue') === 'documents' ? 'active' : '' }}" href="{{ route('admin.bookings', ['queue' => 'documents']) }}">Documents</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-chart-bar"></i></span>
                            <span class="nav-link-title">Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <div class="ota-sidebar-section"><span>Network</span></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.agents') ? 'active' : '' }}" href="{{ route('admin.agents') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-users"></i></span>
                            <span class="nav-link-title">Agents</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.agent-applications.*') ? 'active' : '' }}" href="{{ route('admin.agent-applications.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-user-plus"></i></span>
                            <span class="nav-link-title">Agent applications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.staff') ? 'active' : '' }}" href="{{ route('admin.staff') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-id-badge"></i></span>
                            <span class="nav-link-title">Staff</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-user-cog"></i></span>
                            <span class="nav-link-title">Users &amp; Access</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <div class="ota-sidebar-section"><span>Finance</span></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.commissions*') ? 'active' : '' }}" href="{{ route('admin.commissions.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-receipt"></i></span>
                            <span class="nav-link-title">Commissions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.markups*') ? 'active' : '' }}" href="{{ route('admin.markups') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-currency-dollar"></i></span>
                            <span class="nav-link-title">Markups</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <div class="ota-sidebar-section"><span>Suppliers</span></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.api-settings*') ? 'active' : '' }}" href="{{ route('admin.api-settings') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-api"></i></span>
                            <span class="nav-link-title">API Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <div class="ota-sidebar-section"><span>Website</span></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.branding.*') ? 'active' : '' }}" href="{{ route('admin.settings.branding.edit') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-brush"></i></span>
                            <span class="nav-link-title">Branding</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.homepage.*') ? 'active' : '' }}" href="{{ route('admin.settings.homepage.edit') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-layout"></i></span>
                            <span class="nav-link-title">Homepage</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.media.*') ? 'active' : '' }}" href="{{ route('admin.settings.media.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-photo"></i></span>
                            <span class="nav-link-title">Media Library</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <div class="ota-sidebar-section"><span>Communications</span></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.communications.index') ? 'active' : '' }}" href="{{ route('admin.settings.communications.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-mail-cog"></i></span>
                            <span class="nav-link-title">Communications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.communications.templates.*') ? 'active' : '' }}" href="{{ route('admin.settings.communications.templates.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-template"></i></span>
                            <span class="nav-link-title">Message Templates</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <div class="ota-sidebar-section"><span>System</span></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.roles-permissions') ? 'active' : '' }}" href="{{ route('admin.roles-permissions') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-shield-lock"></i></span>
                            <span class="nav-link-title">Roles &amp; Permissions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.go-live-checklist') ? 'active' : '' }}" href="{{ route('admin.go-live-checklist') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-list-check"></i></span>
                            <span class="nav-link-title">Go-live checklist</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.system-health') ? 'active' : '' }}" href="{{ route('admin.system-health') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-heartbeat"></i></span>
                            <span class="nav-link-title">System health</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.deployment-checklist') ? 'active' : '' }}" href="{{ route('admin.deployment-checklist') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-shield-check"></i></span>
                            <span class="nav-link-title">Deployment checklist</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <div class="ota-sidebar-section"><span>Portals</span></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}" href="{{ route('staff.dashboard') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-briefcase"></i></span>
                            <span class="nav-link-title">Staff portal</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}" href="{{ route('agent.dashboard') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-building-store"></i></span>
                            <span class="nav-link-title">Agent portal</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}" href="{{ route('customer.dashboard') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-user"></i></span>
                            <span class="nav-link-title">Customer portal</span>
                        </a>
                    </li>
                    @stack('sidebar-nav')
                </ul>
                @if (request()->is('admin*'))
                    <div class="px-3 py-3 mt-2 border-top border-secondary border-opacity-25">
                        <p class="text-secondary mb-0" style="font-size: 0.7rem; line-height: 1.35;">Operational access is scoped by account type, agency context, and policies.</p>
                    </div>
                @endif
            </div>
        </div>
    </aside>

    <div class="page-wrapper">
        @if (request()->is('admin*'))
            <div class="container-xl pt-3 pb-0">
                <div class="alert alert-warning ops-admin-banner mb-3 py-2" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-info-circle fs-4"></i>
                        <span>Supplier connections and ticketing providers may still require final API onboarding. Manual review remains available.</span>
                    </div>
                </div>
            </div>
        @endif
        @hasSection('page-header')
            <div class="page-header d-print-none">
                <div class="container-xl">
                    @yield('page-header')
                </div>
            </div>
        @endif

        <main class="page-body">
            <div class="container-xl py-4">
                {{-- Dynamic: alerts, breadcrumbs, Livewire, etc. --}}
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script src="{{ $tabler }}/js/tabler.min.js" defer></script>
@stack('scripts')
</body>
</html>
