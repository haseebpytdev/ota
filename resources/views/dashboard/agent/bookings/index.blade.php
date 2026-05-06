@extends('layouts.dashboard')

@section('title', 'My Bookings')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Agent Portal</div>
            <h1 class="page-title">My bookings</h1>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('agent.bookings.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Create booking request
            </a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $k = $kpis ?? [];
    @endphp

    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm ota-kpi-card">
                <div class="card-body">
                    <div class="text-secondary">My bookings</div>
                    <div class="h2 mb-0">{{ number_format((int) ($k['my_bookings'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm ota-kpi-card ota-kpi-accent-amber">
                <div class="card-body">
                    <div class="text-secondary">Pending</div>
                    <div class="h2 mb-0">{{ number_format((int) ($k['pending'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm ota-kpi-card ota-kpi-accent-emerald">
                <div class="card-body">
                    <div class="text-secondary">Confirmed</div>
                    <div class="h2 mb-0">{{ number_format((int) ($k['confirmed'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm ota-kpi-card ota-kpi-accent-violet">
                <div class="card-body">
                    <div class="text-secondary">Ticketed / monthly sales</div>
                    <div class="h2 mb-0">{{ number_format((int) ($k['ticketed'] ?? 0)) }} / Rs {{ number_format((float) ($k['monthly_sales'] ?? 0), 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table ota-admin-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ref</th>
                        <th>Customer/passenger</th>
                        <th>Route</th>
                        <th>Airline</th>
                        <th>Travel date</th>
                        <th class="text-end">Total fare</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        @php
                            $pax = $booking->passengers->first();
                            $customer = trim(implode(' ', array_filter([$pax?->title, $pax?->first_name, $pax?->last_name]))) ?: ($booking->contact?->email ?? '—');
                        @endphp
                        <tr>
                            <td class="fw-semibold text-secondary">{{ $booking->booking_reference ?? 'Draft' }}</td>
                            <td>{{ $customer }}</td>
                            <td>{{ $booking->route ?? '—' }}</td>
                            <td>{{ $booking->airline ?? '—' }}</td>
                            <td>{{ $booking->travel_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-end fw-semibold">Rs {{ number_format((float) ($booking->fareBreakdown?->total ?? 0), 0) }}</td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $booking->status->value) }}</td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', (string) ($booking->payment_status ?? 'unpaid')) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('agent.bookings.show', $booking) }}">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-4">No bookings yet. Create your first request to begin.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $bookings->links() }}
        </div>
    </div>
@endsection
