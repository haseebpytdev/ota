@extends('layouts.dashboard')

@section('title', 'Agent applications')

@php
    /**
     * Phase 23B.7.2 — Agent Applications review queue.
     *
     *  Status palette (spec):
     *    pending          → amber      (badge-soft-warning)
     *    approved         → green      (badge-soft-success)
     *    rejected         → red        (badge-soft-danger)
     *    needs_more_info  → purple/grey (badge-soft-purple)
     *
     *  We render a separate "Converted" blue badge alongside the status when
     *  the applicant's email matches an existing Agent record.
     */
    $statusBadgeFor = static fn (string $status): string => match ($status) {
        'pending' => 'badge-soft-warning',
        'approved' => 'badge-soft-success',
        'rejected' => 'badge-soft-danger',
        'needs_more_info' => 'badge-soft-purple',
        default => 'badge-soft-neutral',
    };
    $statusLabelFor = static fn (string $status): string => match ($status) {
        'needs_more_info' => 'Needs info',
        default => str($status)->replace('_', ' ')->title()->toString(),
    };
    $selected = $selectedApplication ?? null;
    $duplicateEmailCounts = $duplicateEmailCounts ?? [];
    $hasFilters = ($filters['search'] ?? '') !== ''
        || ($filters['status'] ?? '') !== ''
        || ($filters['submitted_from'] ?? '') !== ''
        || ($filters['submitted_to'] ?? '') !== ''
        || ($filters['city_country'] ?? '') !== ''
        || ($filters['duplicate_only'] ?? false);
@endphp

@section('page-header')
    <div class="row g-2 align-items-center" data-testid="ota-agent-applications-page-header">
        <div class="col">
            <div class="page-pretitle">Partner onboarding</div>
            <h1 class="page-title">Agent applications</h1>
            <div class="text-secondary mt-1">
                Review partner applications, approve qualified agents, and track onboarding status.
            </div>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('admin.agent-applications.export', request()->query()) }}"
               class="btn btn-outline-secondary"
               data-testid="ota-agent-applications-export-header">
                <i class="ti ti-download me-1"></i> Export applications CSV
            </a>
        </div>
    </div>
@endsection

@section('content')
<style>
    [data-agent-applications-page] { max-width: 1540px; margin: 0 auto; }
    .application-kpi .card { height: 100%; border: 1px solid rgba(98, 105, 118, 0.16); }
    .application-kpi .card-body { padding: 0.85rem 1rem; }
    .application-kpi .h2 { font-size: 1.35rem; margin-bottom: 0; font-variant-numeric: tabular-nums; }
    .application-kpi-link { display: block; height: 100%; color: inherit; text-decoration: none; border-radius: 0.5rem; }
    .application-kpi-link .card { transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease; }
    .application-kpi-link:hover .card,
    .application-kpi-link.is-active .card { border-color: #93c5fd; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08); background: #f8fbff; }

    .applications-filters {
        background: #f8fafc;
        border: 1px solid rgba(98, 105, 118, 0.14);
        border-radius: 10px;
        padding: 1rem 1.1rem;
    }
    .applications-filters .form-label {
        font-size: 0.72rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 0.3rem;
    }
    .application-filter-row { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.75rem; align-items: end; }
    .application-filter-search { grid-column: span 2; }
    .application-filter-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        align-items: center;
        padding-top: 0.85rem;
        margin-top: 0.85rem;
        border-top: 1px dashed rgba(148, 163, 184, 0.35);
    }

    .applications-list-wrap {
        border: 1px solid rgba(98, 105, 118, 0.12);
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }
    .applications-table { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0; font-size: 0.84rem; }
    .applications-table th,
    .applications-table td { white-space: normal; vertical-align: top; }
    .applications-table .col-applicant { width: 20%; }
    .applications-table .col-company { width: 20%; }
    .applications-table .col-contact { width: 22%; }
    .applications-table .col-status { width: 10%; }
    .applications-table .col-submitted { width: 12%; }
    .applications-table .col-risk { width: 10%; }
    .applications-table .col-action { width: 6%; text-align: right; }
    .applications-table thead th {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
        font-weight: 700;
        background: #f8fafc;
        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        padding: 0.55rem 0.75rem;
    }
    .applications-table tbody td {
        padding: 0.7rem 0.75rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.85);
        color: #0f172a;
    }
    .applications-table tbody tr { transition: background-color 0.12s ease; }
    .applications-table tbody tr:hover { background: #f8fbff; }
    .applications-table tbody tr.is-active { background: #eff6ff; box-shadow: inset 3px 0 0 #3b82f6; }
    .application-primary { font-weight: 700; color: #1d4ed8; line-height: 1.15; }
    .application-muted { color: #64748b; font-size: 0.76rem; line-height: 1.35; }
    .application-risk-list { display: flex; flex-wrap: wrap; gap: 0.25rem; }

    .application-preview { position: sticky; top: 24px; }
    .application-preview .card { border: 1px solid rgba(98, 105, 118, 0.16); box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06); }
    .preview-section { margin-bottom: 0.85rem; }
    .preview-section:last-child { margin-bottom: 0; }
    .preview-section-title {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #475569;
        font-weight: 700;
        margin: 0 0 0.4rem 0;
    }
    .preview-block {
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 8px;
        padding: 0.65rem 0.75rem;
        margin-bottom: 0.6rem;
        background: #fff;
    }
    .preview-kv { display: flex; justify-content: space-between; gap: 0.8rem; font-size: 0.86rem; padding: 0.18rem 0; }
    .preview-kv strong { color: #0f172a; text-align: right; }
    .workflow-steps { display: grid; gap: 0.45rem; }
    .workflow-step { display: flex; align-items: center; gap: 0.5rem; color: #64748b; font-size: 0.84rem; }
    .workflow-step.is-done { color: #166534; font-weight: 600; }

    /* Spec status palette: amber pending / green approved / red rejected /
       purple-grey needs_info / blue converted / neutral grey fallback. */
    .badge-soft-success { background: #dcfce7; color: #166534 !important; border: 1px solid #bbf7d0; }
    .badge-soft-warning { background: #fef3c7; color: #92400e !important; border: 1px solid #fde68a; }
    .badge-soft-danger  { background: #fee2e2; color: #991b1b !important; border: 1px solid #fecaca; }
    .badge-soft-info    { background: #dbeafe; color: #1e40af !important; border: 1px solid #bfdbfe; }
    .badge-soft-purple  { background: #ede9fe; color: #5b21b6 !important; border: 1px solid #ddd6fe; }
    .badge-soft-neutral { background: #e5e7eb; color: #374151 !important; border: 1px solid #d1d5db; }
    .badge-soft-risk    { background: #fff7ed; color: #9a3412 !important; border: 1px solid #fed7aa; }
    .badge-soft-converted { background: #dbeafe; color: #1e40af !important; border: 1px solid #bfdbfe; }

    /* "2 applications from this email" duplicate inline alert */
    .application-duplicate-alert {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #fff7ed;
        border: 1px solid #fdba74;
        border-radius: 8px;
        padding: 0.55rem 0.7rem;
        color: #7c2d12;
        font-size: 0.84rem;
        margin-bottom: 0.6rem;
    }
    .application-duplicate-alert .ti { color: #c2410c; font-size: 1.1rem; }
    .application-duplicate-alert strong { color: #7c2d12; }

    .preview-actions { display: grid; gap: 0.45rem; }
    .preview-actions .btn {
        width: 100%;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.45rem 0.7rem;
        font-size: 0.85rem;
    }
    .preview-actions .btn[aria-disabled="true"] {
        background: #f1f5f9 !important;
        color: #94a3b8 !important;
        border-color: rgba(148, 163, 184, 0.4) !important;
        cursor: not-allowed;
        pointer-events: none;
    }
    .preview-actions .action-helper {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-left: auto;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .applications-empty-state {
        border: 1px dashed rgba(148, 163, 184, 0.5);
        border-radius: 10px;
        padding: 1.5rem 0.75rem;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        margin: 0.85rem;
    }

    @media (max-width: 1199.98px) {
        .application-filter-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .application-filter-search { grid-column: span 3; }
    }
    @media (max-width: 991.98px) {
        .application-preview { position: static; }
        .applications-table { table-layout: auto; }
        .applications-table thead {
            border: 0; clip: rect(0 0 0 0); height: 1px; margin: -1px;
            overflow: hidden; padding: 0; position: absolute; width: 1px;
        }
        .applications-table,
        .applications-table tbody,
        .applications-table tr,
        .applications-table td { display: block; width: 100%; box-sizing: border-box; }
        .applications-table tbody tr {
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 10px;
            margin: 0.55rem 0.75rem;
            padding: 0.55rem 0.7rem;
            background: #fff;
        }
        .applications-table tbody td {
            border-bottom: 1px dashed rgba(148, 163, 184, 0.25);
            padding: 0.4rem 0;
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            text-align: right;
        }
        .applications-table tbody td::before {
            content: attr(data-label);
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 700;
            text-align: left;
        }
        .applications-table .col-applicant,
        .applications-table .col-company,
        .applications-table .col-contact { text-align: left; display: block; }
        .applications-table .col-applicant::before,
        .applications-table .col-company::before,
        .applications-table .col-contact::before { display: none; }
        .applications-table .col-action { justify-content: flex-end; border-bottom: 0; }
    }
    @media (max-width: 640px) {
        .application-filter-row { grid-template-columns: 1fr; }
        .application-filter-search { grid-column: span 1; }
        .application-filter-actions { flex-direction: column; align-items: stretch; }
        .application-filter-actions .btn,
        .application-filter-actions .ms-auto { width: 100%; margin-left: 0 !important; }
    }
</style>

<div data-agent-applications-page>
    <div class="row row-cards application-kpi mb-3" data-testid="ota-agent-applications-kpis">
        @foreach ([
            ['label' => 'Total applications', 'value' => $kpis['total'] ?? 0, 'href' => route('admin.agent-applications.index'), 'class' => '', 'helper' => 'All submitted partner requests'],
            ['label' => 'Pending review', 'value' => $kpis['pending'] ?? 0, 'href' => route('admin.agent-applications.index', ['status' => 'pending']), 'class' => 'text-warning', 'helper' => 'Awaiting onboarding decision'],
            ['label' => 'Approved', 'value' => $kpis['approved'] ?? 0, 'href' => route('admin.agent-applications.index', ['status' => 'approved']), 'class' => 'text-success', 'helper' => 'Accepted applications'],
            ['label' => 'Rejected', 'value' => $kpis['rejected'] ?? 0, 'href' => route('admin.agent-applications.index', ['status' => 'rejected']), 'class' => 'text-danger', 'helper' => 'Declined requests'],
            ['label' => 'Converted to agent', 'value' => $kpis['converted'] ?? 0, 'href' => route('admin.agents'), 'class' => 'text-primary', 'helper' => 'Matching active agent records'],
            ['label' => 'Duplicate emails', 'value' => $kpis['duplicates'] ?? 0, 'href' => route('admin.agent-applications.index', ['duplicate_only' => 1]), 'class' => 'text-warning', 'helper' => 'Rows with repeated email'],
        ] as $kpi)
            <div class="col-sm-6 col-lg-4 col-xl-2">
                <a href="{{ $kpi['href'] }}" class="application-kpi-link {{ request()->fullUrlIs($kpi['href']) ? 'is-active' : '' }}">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="text-secondary small">{{ $kpi['label'] }}</div>
                            <div class="h2 {{ $kpi['class'] }}">{{ number_format((int) $kpi['value']) }}</div>
                            <div class="text-secondary small mt-1">{{ $kpi['helper'] }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="applications-filters mb-3" data-testid="ota-agent-applications-filter-card">
        <form method="get" action="{{ route('admin.agent-applications.index') }}" id="agent-applications-filter-form">
            <div class="application-filter-row">
                <div class="application-filter-search">
                    <label class="form-label" for="application-search">Search</label>
                    <input id="application-search"
                           type="text"
                           name="search"
                           class="form-control"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Search name, company, email, phone">
                </div>
                <div>
                    <label class="form-label" for="application-status">Status</label>
                    <select id="application-status" class="form-select" name="status">
                        <option value="">All statuses</option>
                        @foreach (['pending', 'approved', 'rejected', 'needs_more_info'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $statusLabelFor($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="application-submitted-from">Submitted from</label>
                    <input id="application-submitted-from" type="date" name="submitted_from" class="form-control" value="{{ $filters['submitted_from'] ?? '' }}">
                </div>
                <div>
                    <label class="form-label" for="application-submitted-to">Submitted to</label>
                    <input id="application-submitted-to" type="date" name="submitted_to" class="form-control" value="{{ $filters['submitted_to'] ?? '' }}">
                </div>
                <div>
                    <label class="form-label" for="application-city-country">City/country</label>
                    <input id="application-city-country" type="text" name="city_country" class="form-control" value="{{ $filters['city_country'] ?? '' }}" placeholder="Lahore or Pakistan">
                </div>
            </div>

            <div class="application-filter-actions" data-testid="ota-agent-applications-filter-actions">
                <label class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="duplicate_only" value="1" @checked($filters['duplicate_only'] ?? false)>
                    <span class="form-check-label">Duplicate only</span>
                </label>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-filter me-1"></i> Apply filters
                </button>
                <a href="{{ route('admin.agent-applications.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-x me-1"></i> Reset
                </a>
                <a href="{{ route('admin.agent-applications.export', request()->query()) }}"
                   class="btn btn-outline-secondary ms-auto"
                   data-testid="ota-agent-applications-export-csv">
                    <i class="ti ti-download me-1"></i> Export applications CSV
                </a>
            </div>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card applications-list-wrap" data-testid="ota-agent-applications-list">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h3 class="card-title mb-0">Application queue</h3>
                        <div class="card-subtitle text-secondary">
                            Showing <strong>{{ number_format($applications->count()) }}</strong>
                            of <strong>{{ number_format($applications->total()) }}</strong>
                            application{{ $applications->total() === 1 ? '' : 's' }}@if($hasFilters)<span> · filters applied</span>@endif
                        </div>
                    </div>
                </div>

                @if ($applications->isEmpty())
                    <div class="applications-empty-state" data-testid="ota-agent-applications-empty">
                        <i class="ti ti-inbox d-block mb-2 fs-2 text-muted"></i>
                        <strong class="d-block mb-1">{{ $hasFilters ? 'No applications match your filters' : 'No applications yet' }}</strong>
                        <p class="mb-3">
                            {{ $hasFilters ? 'Try a different status, date range, or duplicate filter.' : 'New partner requests will appear here after agents submit the registration form.' }}
                        </p>
                        @if (! $hasFilters)
                            <div class="btn-list justify-content-center">
                                <a href="{{ route('agent.register.form') }}" class="btn btn-sm btn-primary" data-testid="ota-agent-applications-empty-registration">
                                    <i class="ti ti-external-link me-1"></i> View agent registration page
                                </a>
                                <a href="{{ route('admin.agents') }}" class="btn btn-sm btn-outline-secondary" data-testid="ota-agent-applications-empty-back-agents">
                                    <i class="ti ti-users me-1"></i> Back to agents
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <table class="applications-table" data-testid="ota-agent-applications-table">
                        <thead>
                            <tr>
                                <th class="col-applicant">Applicant</th>
                                <th class="col-company">Company</th>
                                <th class="col-contact">Contact</th>
                                <th class="col-status">Status</th>
                                <th class="col-submitted">Submitted</th>
                                <th class="col-risk">Flags</th>
                                <th class="col-action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($applications as $application)
                                @php
                                    $emailKey = strtolower((string) $application->email);
                                    $isDuplicate = in_array($emailKey, $duplicateEmailKeys ?? [], true);
                                    $isConverted = in_array($emailKey, $convertedEmailKeys ?? [], true);
                                    $missingPhone = trim((string) $application->mobile) === '';
                                    $isSelected = $selected && (int) $selected->id === (int) $application->id;
                                @endphp
                                <tr class="{{ $isSelected ? 'is-active' : '' }}" data-testid="ota-agent-application-row-{{ $application->id }}">
                                    <td class="col-applicant" data-label="Applicant">
                                        <a class="application-primary" href="{{ route('admin.agent-applications.index', array_merge(request()->query(), ['preview' => $application->id])) }}">
                                            {{ trim($application->first_name.' '.$application->last_name) }}
                                        </a>
                                        <div class="application-muted">{{ $application->business_type }}</div>
                                    </td>
                                    <td class="col-company" data-label="Company">
                                        <strong>{{ $application->company_name }}</strong>
                                        <div class="application-muted">{{ $application->city }}, {{ $application->country }}</div>
                                    </td>
                                    <td class="col-contact" data-label="Contact">
                                        <div>{{ $application->email }}</div>
                                        <div class="application-muted">{{ $application->mobile }}</div>
                                    </td>
                                    <td class="col-status" data-label="Status">
                                        <span class="badge {{ $statusBadgeFor($application->status) }}" data-testid="ota-agent-application-status-{{ $application->status }}">
                                            {{ $statusLabelFor($application->status) }}
                                        </span>
                                        @if ($isConverted)
                                            <span class="badge badge-soft-converted mt-1" data-testid="ota-agent-application-status-converted">Converted</span>
                                        @endif
                                    </td>
                                    <td class="col-submitted" data-label="Submitted">
                                        {{ $application->created_at?->format('Y-m-d H:i') }}
                                        <div class="application-muted">{{ $application->created_at?->diffForHumans() }}</div>
                                    </td>
                                    <td class="col-risk" data-label="Flags">
                                        <div class="application-risk-list">
                                            @if ($isDuplicate)
                                                <span class="badge badge-soft-risk" data-testid="ota-agent-application-risk-duplicate" title="{{ (int) ($duplicateEmailCounts[$emailKey] ?? 0) }} applications from this email">Duplicate email</span>
                                            @endif
                                            @if ($isConverted)
                                                <span class="badge badge-soft-converted" data-testid="ota-agent-application-risk-converted">Already agent</span>
                                            @endif
                                            @if ($missingPhone)
                                                <span class="badge badge-soft-neutral" data-testid="ota-agent-application-risk-missing-phone">Missing phone</span>
                                            @endif
                                            @if (! $isDuplicate && ! $isConverted && ! $missingPhone)
                                                <span class="text-secondary small">No flags</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="col-action" data-label="Action">
                                        <a class="btn btn-sm btn-primary" href="{{ route('admin.agent-applications.show', $application) }}">Review</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($applications->hasPages())
                    <div class="card-footer">
                        {{ $applications->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4 col-lg-5" data-testid="ota-agent-applications-preview">
            <div class="application-preview">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Application preview</h3>
                        <div class="card-subtitle text-secondary">
                            @if ($selected)
                                {{ trim($selected->first_name.' '.$selected->last_name) }}
                            @else
                                Select an application from the queue.
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($selected)
                            @php
                                $selectedEmailKey = strtolower((string) $selected->email);
                                $selectedDuplicate = (bool) ($selected->is_duplicate_email ?? in_array($selectedEmailKey, $duplicateEmailKeys ?? [], true));
                                $selectedConverted = (bool) ($selected->is_converted_to_agent ?? in_array($selectedEmailKey, $convertedEmailKeys ?? [], true));
                                $selectedDuplicateCount = (int) ($selected->duplicate_email_count ?? ($duplicateEmailCounts[$selectedEmailKey] ?? 0));
                                $selectedMissingPhone = trim((string) $selected->mobile) === '';
                            @endphp

                            @if ($selectedDuplicate)
                                <div class="application-duplicate-alert" data-testid="ota-agent-application-preview-duplicate-warning">
                                    <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                                    <div>
                                        <strong>Duplicate email detected</strong><br>
                                        {{ number_format($selectedDuplicateCount) }} applications from this email
                                    </div>
                                </div>
                            @endif

                            <section class="preview-section" data-testid="ota-agent-application-preview-profile">
                                <h6 class="preview-section-title"><i class="ti ti-user"></i> Applicant profile</h6>
                                <div class="preview-block">
                                    <div class="preview-kv"><span>Name</span><strong>{{ trim($selected->first_name.' '.$selected->last_name) }}</strong></div>
                                    <div class="preview-kv"><span>Company</span><strong>{{ $selected->company_name }}</strong></div>
                                    <div class="preview-kv"><span>Submitted date</span><strong>{{ $selected->created_at?->format('Y-m-d H:i') }}</strong></div>
                                    <div class="preview-kv"><span>Status</span><strong><span class="badge {{ $statusBadgeFor($selected->status) }}">{{ $statusLabelFor($selected->status) }}</span></strong></div>
                                    @if ($selectedConverted)
                                        <div class="preview-kv"><span>Conversion</span><strong><span class="badge badge-soft-converted">Converted</span></strong></div>
                                    @endif
                                </div>
                            </section>

                            <section class="preview-section" data-testid="ota-agent-application-preview-contact">
                                <h6 class="preview-section-title"><i class="ti ti-address-book"></i> Contact</h6>
                                <div class="preview-block">
                                    <div class="preview-kv"><span>Email</span><strong>{{ $selected->email }}</strong></div>
                                    <div class="preview-kv"><span>Phone</span><strong>{{ $selected->mobile ?: '—' }}</strong></div>
                                    <div class="preview-kv"><span>City/country</span><strong>{{ $selected->city }}, {{ $selected->country }}</strong></div>
                                    <div class="preview-kv"><span>Website</span><strong>{{ $selected->website ?: '—' }}</strong></div>
                                </div>
                            </section>

                            <section class="preview-section" data-testid="ota-agent-application-preview-details">
                                <h6 class="preview-section-title"><i class="ti ti-file-description"></i> Application details</h6>
                                <div class="preview-block">
                                    <div class="preview-kv"><span>Business type</span><strong>{{ $selected->business_type ?: '—' }}</strong></div>
                                    <div class="preview-kv"><span>Expected sales/market</span><strong>{{ $selected->expected_booking_volume ?: '—' }}</strong></div>
                                    <div class="preview-kv"><span>Services</span><strong>{{ is_array($selected->services_interested) ? implode(', ', $selected->services_interested) : '—' }}</strong></div>
                                    <div class="mt-2 small text-secondary"><strong>Message/notes:</strong> {{ $selected->notes ?: 'No applicant notes provided.' }}</div>
                                </div>
                            </section>

                            <section class="preview-section" data-testid="ota-agent-application-preview-risk">
                                <h6 class="preview-section-title"><i class="ti ti-alert-triangle"></i> Risk / duplicate check</h6>
                                <div class="preview-block">
                                    <div class="preview-kv">
                                        <span>Duplicate email count</span>
                                        <strong>
                                            @if ($selectedDuplicate)
                                                <span class="badge badge-soft-risk">{{ number_format($selectedDuplicateCount) }} applications</span>
                                            @else
                                                <span class="text-muted">No duplicates</span>
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="preview-kv">
                                        <span>Existing agent match</span>
                                        <strong>
                                            @if ($selectedConverted)
                                                <span class="badge badge-soft-converted">Already agent</span>
                                            @else
                                                <span class="text-muted">Not yet</span>
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="preview-kv">
                                        <span>Missing required fields</span>
                                        <strong>
                                            @if ($selectedMissingPhone)
                                                <span class="badge badge-soft-neutral">Missing phone</span>
                                            @else
                                                <span class="text-muted">None detected</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                            </section>

                            <section class="preview-section" data-testid="ota-agent-application-preview-actions">
                                <h6 class="preview-section-title"><i class="ti ti-bolt"></i> Actions</h6>
                                <div class="preview-actions">
                                    <a href="{{ route('admin.agent-applications.show', $selected) }}" class="btn btn-primary" data-testid="ota-agent-application-action-open-review">
                                        <i class="ti ti-clipboard-check"></i> Open review
                                    </a>
                                    <button type="button" class="btn btn-outline-success" aria-disabled="true" data-testid="ota-agent-application-action-approve-placeholder">
                                        <i class="ti ti-user-check"></i> Approve as agent <span class="action-helper">Open review</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" aria-disabled="true" data-testid="ota-agent-application-action-reject-placeholder">
                                        <i class="ti ti-user-x"></i> Reject <span class="action-helper">Open review</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" aria-disabled="true" data-testid="ota-agent-application-action-info-placeholder">
                                        <i class="ti ti-message-question"></i> Request more info <span class="action-helper">Open review</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" aria-disabled="true" data-testid="ota-agent-application-action-note-placeholder">
                                        <i class="ti ti-notes"></i> Add internal note <span class="action-helper">Open review</span>
                                    </button>
                                </div>
                            </section>
                        @else
                            <div class="applications-empty-state mb-0">
                                <i class="ti ti-user-search d-block mb-2 fs-2 text-muted"></i>
                                <strong class="d-block mb-1">No application selected</strong>
                                Select an application to inspect applicant details, risk flags, and onboarding status.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
