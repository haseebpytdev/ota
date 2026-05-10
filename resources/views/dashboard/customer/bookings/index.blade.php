@extends('layouts.dashboard')

@section('title', 'My bookings')

@section('page-header')
    <h1 class="page-title">My bookings</h1>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Route</th>
                    <th>Travel date</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($bookings as $booking)
                    <tr>
                        <td>{{ $booking->booking_reference ?? 'N/A' }}</td>
                        <td>{{ $booking->route ?? 'N/A' }}</td>
                        <td>{{ $booking->travel_date?->format('Y-m-d') ?? 'N/A' }}</td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', $booking->status->value) }}</td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', (string) $booking->payment_status) }}</td>
                        <td><a href="{{ route('customer.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary">No bookings found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $bookings->links() }}</div>
@endsection
