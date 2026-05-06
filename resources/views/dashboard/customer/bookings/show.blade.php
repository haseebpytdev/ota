@extends('layouts.dashboard')

@php
    $guest = $isGuestView ?? false;
@endphp

@section('title', 'Booking '.($booking->booking_reference ?? '#'.$booking->id))

@section('page-header')
    <h1 class="page-title">Booking {{ $booking->booking_reference ?? '#'.$booking->id }}</h1>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Trip summary</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Route:</strong> {{ $booking->route ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Status:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', $booking->status->value) }}</span></p>
                    <p class="mb-0"><strong>Payment status:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', (string) $booking->payment_status) }}</span></p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Passengers and contact</h3></div>
                <div class="card-body">
                    @foreach($booking->passengers as $passenger)
                        <div>{{ $passenger->title }} {{ $passenger->first_name }} {{ $passenger->last_name }}</div>
                    @endforeach
                    <hr>
                    <div>Email: {{ $booking->contact?->email ?? 'N/A' }}</div>
                    <div>Phone: {{ $booking->contact?->phone ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Status timeline</h3></div>
                <div class="card-body">
                    @foreach($booking->statusLogs->sortByDesc('created_at') as $log)
                        <div class="small mb-2">
                            {{ $log->created_at?->format('Y-m-d H:i') }}:
                            {{ str_replace('_', ' ', (string) ($log->from_status ?? 'N/A')) }} -> {{ str_replace('_', ' ', (string) $log->to_status) }}
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Communication</h3></div>
                <div class="card-body">
                    @php
                        $safeEvents = ['booking_request_received', 'booking_confirmed', 'booking_status_changed', 'payment_verified', 'payment_rejected', 'ticket_issued'];
                    @endphp
                    @forelse($booking->communicationLogs->where('channel', 'email')->whereIn('event', $safeEvents)->sortByDesc('created_at') as $log)
                        <div class="small mb-2">{{ $log->created_at?->format('Y-m-d H:i') }} - {{ str_replace('_', ' ', $log->event) }}</div>
                    @empty
                        <div class="text-secondary small">No customer-facing communication yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Payment summary</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Total:</strong> {{ number_format((float) ($booking->fareBreakdown?->total ?? 0), 2) }} {{ $booking->currency }}</p>
                    <p class="mb-1"><strong>Paid:</strong> {{ number_format((float) ($booking->amount_paid ?? 0), 2) }} {{ $booking->currency }}</p>
                    <p class="mb-3"><strong>Balance:</strong> {{ number_format((float) ($booking->balance_due ?? 0), 2) }} {{ $booking->currency }}</p>
                    <form method="post" action="{{ $guest ? route('guest.bookings.payment-proof', ['booking' => $booking, 'token' => $guestToken]) : route('customer.bookings.payment-proof', $booking) }}">
                        @csrf
                        <div class="mb-2">
                            <select name="method" class="form-select" required>
                                @foreach (['bank_transfer', 'cash', 'card_manual', 'easypaisa', 'jazzcash', 'other'] as $m)
                                    <option value="{{ $m }}">{{ str_replace('_', ' ', $m) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
                        <div class="mb-2"><input type="text" name="payment_reference" class="form-control" placeholder="Reference"></div>
                        <div class="mb-2"><textarea name="notes" class="form-control" rows="2" placeholder="Notes"></textarea></div>
                        <button class="btn btn-primary w-100" type="submit">Submit payment proof</button>
                    </form>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Request cancellation</h3></div>
                <div class="card-body">
                    <div class="alert alert-warning py-2 px-3 small">
                        Ticketed bookings require manual supplier void/refund handling until API docs are reviewed.
                    </div>
                    <form method="post" action="{{ $guest ? route('guest.bookings.cancellations.store', ['booking' => $booking, 'token' => $guestToken]) : route('customer.bookings.cancellations.store', $booking) }}">
                        @csrf
                        <div class="mb-2">
                            <select class="form-select" name="cancellation_type" required>
                                @foreach (['booking_cancel', 'ticket_void', 'ticket_refund', 'supplier_cancel'] as $type)
                                    <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2"><textarea class="form-control" name="reason" rows="2" placeholder="Reason (optional)"></textarea></div>
                        <button class="btn btn-outline-danger w-100" type="submit">Submit cancellation request</button>
                    </form>
                    <div class="small text-secondary mt-3 mb-1">Current requests</div>
                    @forelse($booking->cancellationRequests->sortByDesc('created_at')->take(5) as $request)
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between">
                                <span>{{ $request->cancellation_type->value }}</span>
                                <span class="text-capitalize">{{ $request->status->value }}</span>
                            </div>
                            <div class="text-secondary">{{ $request->created_at?->format('Y-m-d H:i') }}</div>
                        </div>
                    @empty
                        <div class="small text-secondary">No cancellation requests yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Documents</h3></div>
                <div class="card-body">
                    @forelse($booking->documents->sortByDesc('created_at') as $doc)
                        @if($doc->status->value === 'generated')
                            <div class="mb-2">
                                <div class="small text-capitalize">{{ str_replace('_', ' ', $doc->document_type->value) }}</div>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ $guest ? route('guest.documents.download', ['bookingDocument' => $doc, 'token' => $guestToken]) : route('customer.documents.download', $doc) }}">Download</a>
                            </div>
                        @endif
                    @empty
                        <div class="text-secondary small">No documents.</div>
                    @endforelse
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Ticket details</h3></div>
                <div class="card-body">
                    <div><strong>PNR:</strong> {{ $booking->pnr ?? 'N/A' }}</div>
                    @foreach($booking->tickets as $ticket)
                        <div class="small mt-2">{{ $ticket->ticket_number }} - {{ $ticket->airline_code ?? 'N/A' }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
