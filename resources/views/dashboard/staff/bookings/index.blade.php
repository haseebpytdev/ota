@extends('layouts.dashboard')

@section('title', 'Bookings')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Operations</div>
            <h1 class="page-title">Bookings</h1>
            <div class="text-secondary mt-1">Agency-scoped queue — filters apply to your agency only.</div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $f = $filters ?? [];
        $statusCases = $statusEnumCases ?? [];
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('staff.bookings.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $f['search'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
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
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" value="{{ $f['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" value="{{ $f['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-12">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="assigned_to_me" value="1" id="atm" @checked(!empty($f['assigned_to_me']))>
                        <label class="form-check-label" for="atm">Assigned to me</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('staff.bookings.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Customer</th>
                        <th>Route</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $row)
                        <tr>
                            <td class="fw-semibold">{{ ($row['booking_ref'] ?? '') !== '' ? $row['booking_ref'] : 'Draft #'.$row['id'] }}</td>
                            <td>{{ $row['customer_name'] }}</td>
                            <td class="small">{{ $row['route'] }}</td>
                            <td><span class="badge bg-secondary text-capitalize">{{ str_replace('_', ' ', $row['status']) }}</span></td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $row['payment_status'] ?? '') }}</td>
                            <td><a href="{{ route('staff.bookings.show', $row['id']) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-secondary text-center py-4">No bookings match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bookings instanceof \Illuminate\Contracts\Pagination\Paginator && $bookings->hasPages())
            <div class="card-footer d-flex justify-content-center">{{ $bookings->links() }}</div>
        @endif
    </div>
@endsection
