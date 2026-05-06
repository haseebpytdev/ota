@extends('layouts.dashboard')

@section('title', 'Booking Detail')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Agent Portal</div>
            <h1 class="page-title">Booking {{ $booking->booking_reference ?? 'Draft' }}</h1>
            <div class="text-secondary mt-1">Agency team will confirm fare and ticketing after review.</div>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('agent.bookings.index') }}" class="btn btn-outline-secondary">Back to my bookings</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $pax = $booking->passengers->first();
        $contact = $booking->contact;
        $fare = $booking->fareBreakdown;
    @endphp

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Trip summary</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-secondary small">Route</div><div>{{ $booking->route ?? '—' }}</div></div>
                        <div class="col-md-6"><div class="text-secondary small">Airline</div><div>{{ $booking->airline ?? '—' }}</div></div>
                        <div class="col-md-6"><div class="text-secondary small">Travel date</div><div>{{ $booking->travel_date?->format('Y-m-d') ?? '—' }}</div></div>
                        <div class="col-md-6"><div class="text-secondary small">Current status</div><div class="text-capitalize">{{ str_replace('_', ' ', $booking->status->value) }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Passenger / contact</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-secondary small">Passenger</div><div>{{ trim(implode(' ', array_filter([$pax?->title, $pax?->first_name, $pax?->last_name]))) ?: '—' }}</div></div>
                        <div class="col-md-6"><div class="text-secondary small">Date of birth</div><div>{{ $pax?->date_of_birth?->format('Y-m-d') ?? '—' }}</div></div>
                        <div class="col-md-6"><div class="text-secondary small">Nationality</div><div>{{ $pax?->nationality ?? '—' }}</div></div>
                        <div class="col-md-6"><div class="text-secondary small">Email</div><div>{{ $contact?->email ?? '—' }}</div></div>
                        <div class="col-md-6"><div class="text-secondary small">Mobile</div><div>{{ $contact?->phone ?? '—' }}</div></div>
                        <div class="col-md-6"><div class="text-secondary small">Country</div><div>{{ $contact?->country ?? '—' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Status timeline</h3></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse ($booking->statusLogs as $log)
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <div class="text-capitalize">
                                        {{ str_replace('_', ' ', (string) ($log->from_status ?? 'draft')) }}
                                        <i class="ti ti-arrow-right mx-1"></i>
                                        {{ str_replace('_', ' ', $log->to_status) }}
                                    </div>
                                    <div class="small text-secondary">{{ $log->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                @if ($log->note)
                                    <div class="small text-secondary mt-1">{{ $log->note }}</div>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-secondary">No status events yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Fare breakdown</h3></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-1"><span>Base fare</span><span>Rs {{ number_format((float) ($fare?->base_fare ?? 0), 0) }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span>Taxes</span><span>Rs {{ number_format((float) ($fare?->taxes ?? 0), 0) }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span>Fees</span><span>Rs {{ number_format((float) ($fare?->fees ?? 0), 0) }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span>Markup</span><span>Rs {{ number_format((float) ($fare?->markup ?? 0), 0) }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-top mt-2 fw-bold"><span>Total</span><span>Rs {{ number_format((float) ($fare?->total ?? 0), 0) }}</span></div>
                    <div class="small text-secondary mt-2">Payment status: {{ str_replace('_', ' ', (string) ($booking->payment_status ?? 'unpaid')) }}</div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Payment proof</h3></div>
                <div class="card-body">
                    <p class="small text-secondary mb-2">Amount due: Rs {{ number_format((float) ($booking->balance_due ?? ($fare?->total ?? 0)), 0) }}</p>
                    <p class="small text-secondary">Share transfer/reference details to submit payment proof. Ticketing remains manual.</p>
                    <form method="post" action="{{ route('agent.bookings.payment-proof', $booking) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Method</label>
                            <select name="method" class="form-select" required>
                                @foreach (['bank_transfer', 'cash', 'card_manual', 'easypaisa', 'jazzcash', 'other'] as $m)
                                    <option value="{{ $m }}">{{ str_replace('_', ' ', $m) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Reference</label>
                            <input type="text" name="payment_reference" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">Submit payment proof</button>
                    </form>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Cancellation request</h3></div>
                <div class="card-body">
                    <div class="alert alert-warning py-2 px-3 small">
                        Ticketed bookings require manual supplier void/refund handling until API docs are reviewed.
                    </div>
                    <form method="post" action="{{ route('agent.bookings.cancellations.store', $booking) }}">
                        @csrf
                        <div class="mb-2">
                            <select class="form-select" name="cancellation_type" required>
                                @foreach (['booking_cancel', 'ticket_void', 'ticket_refund', 'supplier_cancel'] as $type)
                                    <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea class="form-control" name="reason" rows="2" placeholder="Reason (optional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">Request cancellation</button>
                    </form>
                    <div class="small text-secondary mt-3 mb-1">My requests</div>
                    @forelse ($booking->cancellationRequests->sortByDesc('created_at')->take(5) as $request)
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between">
                                <span>{{ $request->cancellation_type->value }}</span>
                                <span class="text-capitalize">{{ $request->status->value }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="small text-secondary">No cancellation requests yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
