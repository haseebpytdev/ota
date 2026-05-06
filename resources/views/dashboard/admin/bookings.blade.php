@extends('layouts.dashboard')

@section('title', 'Bookings')

@push('styles')
<style>
    .bookings-kpi .card { border: 1px solid rgba(98,105,118,.16); }
    .bookings-filters { background: var(--tblr-bg-surface, #f8fafc); border-radius: 8px; padding: 1rem 1.25rem; border: 1px solid rgba(98,105,118,.12); }
    .bookings-table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid rgba(98,105,118,.12); }
    .bookings-preview { position: sticky; top: 1rem; }
    .bookings-preview .card { border: 1px solid rgba(98,105,118,.16); box-shadow: 0 4px 24px rgba(15,23,42,.06); }
    .bookings-preview h4 { font-size: 1rem; font-weight: 600; margin-bottom: .75rem; color: var(--tblr-body-color, #1e293b); }
    .fare-line { display: flex; justify-content: space-between; padding: .35rem 0; border-bottom: 1px dashed rgba(98,105,118,.2); font-size: .875rem; }
    .fare-line:last-child { border-bottom: none; font-weight: 700; font-size: 1rem; padding-top: .5rem; }
</style>
@endpush

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Operations</div>
            <h1 class="page-title">Bookings management</h1>
            <div class="text-secondary mt-1">
                @if (!empty($usingDatabase) && $usingDatabase)
                    Live bookings for your agency (database). Provider connections may require manual review when not connected.
                    @if (empty($hasRows))
                        <span class="d-block mt-1 small">No saved bookings yet. New customer and agent booking requests will appear here automatically.</span>
                    @endif
                @else
                    Booking records are currently unavailable. Verify database connectivity and run migrations.
                @endif
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $b = $selectedBooking;
    @endphp

    {{-- KPIs --}}
    <div class="row row-cards bookings-kpi mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Total bookings</div>
                    <div class="h2 mb-0">{{ number_format($kpis['total'] ?? 0) }}</div>
                    <div class="text-secondary mt-1 small">In this list</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Pending</div>
                    <div class="h2 mb-0 text-warning">{{ number_format($kpis['pending'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Ticketed</div>
                    <div class="h2 mb-0 text-success">{{ number_format($kpis['ticketed'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Unpaid / partial</div>
                    <div class="h2 mb-0 text-danger">{{ number_format($kpis['unpaid'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @php
                $f = $filters ?? [];
                $staffOpts = $filterStaffUsers ?? collect();
                $statusCases = $statusEnumCases ?? [];
            @endphp
            <div class="bookings-filters mb-4">
                <form method="get" action="{{ route('admin.bookings') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ $f['search'] ?? '' }}" class="form-control" placeholder="Ref, name, email, phone">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach ($statusCases as $sc)
                                <option value="{{ $sc->value }}" @selected(($f['status'] ?? '') === $sc->value)>{{ str_replace('_', ' ', $sc->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All</option>
                            @foreach (['unpaid', 'partial', 'paid', 'refunded'] as $ps)
                                <option value="{{ $ps }}" @selected(($f['payment_status'] ?? '') === $ps)>{{ ucfirst($ps) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Created from</label>
                        <input type="date" name="date_from" value="{{ $f['date_from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Created to</label>
                        <input type="date" name="date_to" value="{{ $f['date_to'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Assigned staff</label>
                        <select name="assigned_staff_id" class="form-select">
                            <option value="">Any</option>
                            @foreach ($staffOpts as $su)
                                <option value="{{ $su->id }}" @selected((string)($f['assigned_staff_id'] ?? '') === (string) $su->id)>{{ $su->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 mt-2 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                        <a href="{{ route('admin.bookings') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card bookings-table-wrap">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title">All bookings</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking ref</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Route</th>
                                <th>Airline</th>
                                <th>Travel date</th>
                                <th class="text-end">Fare (PKR)</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Assigned</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $row)
                                @php
                                    $ctype = $row['customer_type'] ?? 'guest';
                                    $typeBadge = match ($ctype) {
                                        'agent' => 'bg-warning',
                                        'customer' => 'bg-primary',
                                        default => 'bg-secondary',
                                    };
                                    $st = $row['status'] ?? 'pending';
                                    $stBadge = match ($st) {
                                        'ticketed' => 'bg-success',
                                        'confirmed' => 'bg-info',
                                        'cancelled' => 'bg-dark',
                                        'draft' => 'bg-secondary',
                                        default => 'bg-warning',
                                    };
                                    $pay = $row['payment_status'] ?? 'unpaid';
                                    $payBadge = match ($pay) {
                                        'paid' => 'bg-success',
                                        'partial' => 'bg-warning',
                                        'refunded' => 'bg-secondary',
                                        default => 'bg-danger',
                                    };
                                    $refDisplay = ($row['booking_ref'] ?? '') !== '' ? $row['booking_ref'] : ('Draft #'.($row['id'] ?? ''));
                                    $isSelected = isset($selectedPreviewKey) && (string)($row['preview_query'] ?? '') === (string)$selectedPreviewKey;
                                @endphp
                                <tr class="{{ $isSelected ? 'table-primary' : '' }}">
                                    <td class="fw-semibold text-nowrap">{{ $refDisplay }}</td>
                                    <td>{{ $row['customer_name'] }}</td>
                                    <td><span class="badge {{ $typeBadge }}">{{ ucfirst($ctype) }}</span></td>
                                    <td>{{ $row['route'] }}</td>
                                    <td class="small">{{ $row['airline'] }}</td>
                                    <td class="text-nowrap">{{ $row['travel_date'] }}</td>
                                    <td class="text-end fw-semibold">Rs {{ number_format((int) ($row['total_fare'] ?? 0), 0) }}</td>
                                    <td><span class="badge {{ $stBadge }}">{{ ucfirst($st) }}</span></td>
                                    <td><span class="badge {{ $payBadge }}">{{ ucfirst(str_replace('_', ' ', $pay)) }}</span></td>
                                    <td class="small text-secondary">{{ $row['assigned_staff_name'] ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('admin.bookings.show', $row['id']) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-secondary py-5 text-center">
                                        No bookings in the database for this agency yet. Guest checkout on the storefront will create rows here.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($bookings instanceof \Illuminate\Contracts\Pagination\Paginator && $bookings->hasPages())
                    <div class="card-footer d-flex justify-content-center">
                        {{ $bookings->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="bookings-preview">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Selected booking</h3>
                        <div class="card-subtitle text-secondary">
                            @if (($previewRef ?? '') !== '')
                                Preview: <code>{{ $previewRef }}</code>
                            @else
                                Default preview (first row). Use <strong>View</strong> to switch.
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($b)
                            <p class="small text-muted mb-2">Ref: <strong>{{ ($b['booking_ref'] ?? '') !== '' ? $b['booking_ref'] : ('Draft #'.($b['id'] ?? '')) }}</strong></p>
                            <h4>Traveller / customer</h4>
                            <p class="mb-1 fw-semibold">{{ $b['customer_name'] }}</p>
                            <p class="small text-secondary mb-3">
                                Type: <span class="badge bg-secondary">{{ ucfirst($b['customer_type'] ?? 'guest') }}</span>
                                @if (!empty($b['agent_name']))
                                    <br><span class="mt-1 d-inline-block">Agent: {{ $b['agent_name'] }}</span>
                                @endif
                            </p>

                            <h4>Contact</h4>
                            <ul class="list-unstyled small mb-3">
                                <li><i class="ti ti-phone me-1"></i> {{ $b['contact_phone'] }}</li>
                                <li><i class="ti ti-mail me-1"></i> {{ $b['contact_email'] }}</li>
                            </ul>

                            <h4>Trip</h4>
                            <ul class="list-unstyled small mb-3">
                                <li><strong>Route:</strong> {{ $b['route'] }}</li>
                                <li><strong>Airline:</strong> {{ $b['airline'] }}</li>
                                <li><strong>Travel:</strong> {{ $b['travel_date'] }}</li>
                                <li><strong>Passengers:</strong> {{ $b['passengers_count'] }}</li>
                            </ul>

                            <h4>Fare breakdown</h4>
                            <div class="mb-3">
                                <div class="fare-line"><span>Base fare</span><span>Rs {{ number_format((int) ($b['base_fare'] ?? 0), 0) }}</span></div>
                                <div class="fare-line"><span>Markup &amp; fees</span><span>Rs {{ number_format((int) ($b['markup'] ?? 0), 0) }}</span></div>
                                <div class="fare-line"><span>Total</span><span>Rs {{ number_format((int) ($b['total_fare'] ?? 0), 0) }}</span></div>
                            </div>

                            <div class="alert alert-secondary small mb-3">
                                <strong>Internal note</strong><br>{{ $b['internal_note'] ?? '—' }}
                            </div>

                            <h4>Actions</h4>
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.bookings.show', $b['id']) }}" class="btn btn-primary">Open full record</a>
                            </div>
                            <p class="text-secondary small mt-3 mb-0">Status, notes, and assignments are managed on the booking detail page.</p>
                        @else
                            <p class="text-secondary mb-0">No booking selected.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
