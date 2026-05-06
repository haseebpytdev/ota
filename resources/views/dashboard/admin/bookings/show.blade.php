@extends('layouts.dashboard')

@php
    $p = $portal ?? 'admin';
    $statusUrl = $p === 'staff' ? route('staff.bookings.status', $booking) : route('admin.bookings.status', $booking);
    $noteUrl = $p === 'staff' ? route('staff.bookings.notes', $booking) : route('admin.bookings.notes', $booking);
    $assignUrl = $p === 'admin' ? route('admin.bookings.assign-staff', $booking) : null;
    $listUrl = $p === 'staff' ? route('staff.bookings.index') : route('admin.bookings');
    $docConfirmationUrl = $p === 'staff' ? route('staff.bookings.documents.confirmation', $booking) : route('admin.bookings.documents.confirmation', $booking);
    $docInvoiceUrl = $p === 'staff' ? route('staff.bookings.documents.invoice', $booking) : route('admin.bookings.documents.invoice', $booking);
    $docItineraryUrl = $p === 'staff' ? route('staff.bookings.documents.ticket-itinerary', $booking) : route('admin.bookings.documents.ticket-itinerary', $booking);
    $docDownloadRoute = $p === 'staff' ? 'staff.bookings.documents.download' : 'admin.bookings.documents.download';
    $docReceiptRoute = $p === 'staff' ? 'staff.bookings.payments.documents.receipt' : 'admin.bookings.payments.documents.receipt';
    $cancelStoreUrl = $p === 'staff' ? route('staff.bookings.cancellations.store', $booking) : route('admin.bookings.cancellations.store', $booking);
    $refundStoreUrl = $p === 'staff' ? route('staff.bookings.refunds.store', $booking) : route('admin.bookings.refunds.store', $booking);
@endphp

@section('title', 'Booking '.$booking->booking_reference ?: '#'.$booking->id)

@push('styles')
<style>
    .booking-detail .card { border: 1px solid rgba(98,105,118,.16); }
    .booking-detail h3 { font-size: 1rem; font-weight: 600; }
    .timeline-entry { border-left: 2px solid var(--tblr-primary, #206bc4); padding-left: 1rem; margin-bottom: 1rem; }
    .audit-row { font-size: .8125rem; border-bottom: 1px dashed rgba(98,105,118,.15); padding: .35rem 0; }
</style>
@endpush

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle"><a href="{{ $listUrl }}" class="text-secondary">Bookings</a></div>
            <h1 class="page-title">
                {{ $booking->booking_reference ?: 'Draft #'.$booking->id }}
                <span class="badge bg-azure text-capitalize ms-2">{{ str_replace('_', ' ', $booking->status->value) }}</span>
            </h1>
            <div class="text-secondary mt-1">Payment: <strong class="text-capitalize">{{ str_replace('_', ' ', $booking->payment_status ?? 'unpaid') }}</strong>
                @if($booking->assignedStaff)
                    · Assigned: <strong>{{ $booking->assignedStaff->name }}</strong>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-4 booking-detail">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Trip</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $booking->route ?? '—' }}</strong></p>
                    <p class="text-secondary small mb-0">{{ $booking->airline ?? '—' }} · Travel {{ $booking->travel_date?->format('M j, Y') ?? '—' }} · Supplier {{ $booking->supplier ?? '—' }}</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Passengers &amp; contact</h3></div>
                <div class="card-body">
                    @foreach ($booking->passengers as $pax)
                        <p class="mb-1 fw-semibold">{{ trim(($pax->title.' '.$pax->first_name.' '.$pax->last_name)) }}</p>
                    @endforeach
                    @if($booking->contact)
                        <ul class="list-unstyled small text-secondary mb-0 mt-2">
                            <li><i class="ti ti-mail me-1"></i>{{ $booking->contact->email }}</li>
                            <li><i class="ti ti-phone me-1"></i>{{ $booking->contact->phone ?? '—' }}</li>
                            @if($booking->contact->country)
                                <li><i class="ti ti-map-pin me-1"></i>{{ $booking->contact->country }}</li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Fare</h3></div>
                <div class="card-body">
                    @if($booking->fareBreakdown)
                        @php $f = $booking->fareBreakdown; @endphp
                        <div class="d-flex justify-content-between"><span>Base</span><span>Rs {{ number_format((float) $f->base_fare, 0) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Taxes</span><span>Rs {{ number_format((float) $f->taxes, 0) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Markup</span><span>Rs {{ number_format((float) $f->markup, 0) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Fees</span><span>Rs {{ number_format((float) $f->fees, 0) }}</span></div>
                        <div class="d-flex justify-content-between fw-bold mt-2 pt-2 border-top"><span>Total</span><span>Rs {{ number_format((float) $f->total, 0) }}</span></div>
                    @else
                        <p class="text-secondary mb-0">No fare breakdown.</p>
                    @endif
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Offer validation</h3></div>
                <div class="card-body">
                    @php $meta = $booking->meta ?? []; @endphp
                    <div class="small text-secondary">Status</div>
                    <div class="mb-2 text-capitalize">{{ str_replace('_', ' ', (string) ($meta['offer_validation_status'] ?? 'unknown')) }}</div>
                    <div class="small text-secondary">Validated at</div>
                    <div class="mb-2">{{ $meta['validated_at'] ?? '—' }}</div>
                    <div class="small text-secondary">Supplier</div>
                    <div class="mb-2">{{ $meta['supplier_provider'] ?? '—' }}</div>
                    @if (!empty($meta['validation_warnings']) && is_array($meta['validation_warnings']))
                        <div class="small text-secondary">Warnings</div>
                        <ul class="mb-0">
                            @foreach ($meta['validation_warnings'] as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Internal notes</h3></div>
                <div class="card-body">
                    @forelse ($booking->bookingNotes->sortByDesc('created_at') as $bn)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="small text-secondary">{{ $bn->created_at?->format('Y-m-d H:i') }} · {{ $bn->user?->name ?? 'System' }}
                                @if($bn->is_customer_visible)<span class="badge bg-info ms-1">Customer visible</span>@endif
                            </div>
                            <div class="mt-1">{{ $bn->note }}</div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No notes yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Status timeline</h3></div>
                <div class="card-body">
                    @foreach ($booking->statusLogs->sortBy('created_at') as $log)
                        <div class="timeline-entry">
                            <div class="small text-secondary">{{ $log->created_at?->format('Y-m-d H:i') }} · {{ $log->user?->name ?? '—' }}</div>
                            <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', (string) $log->from_status) ?: '—' }} → {{ str_replace('_', ' ', (string) $log->to_status) }}</div>
                            @if($log->note)<div class="small mt-1">{{ $log->note }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Audit trail</h3></div>
                <div class="card-body">
                    @forelse ($auditLogs as $al)
                        <div class="audit-row">
                            <span class="text-secondary">{{ $al->created_at?->format('Y-m-d H:i') }}</span>
                            · <code>{{ $al->action }}</code>
                            @if($al->user) · {{ $al->user->name }} @endif
                            @if($al->properties)
                                <div class="text-muted small mt-1">{{ json_encode($al->properties) }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No audit entries.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Supplier booking / PNR</h3></div>
                <div class="card-body">
                    @php
                        $meta = $booking->meta ?? [];
                        $provider = $meta['supplier_provider'] ?? ($booking->supplier ?? '—');
                        $validationStatus = $meta['offer_validation_status'] ?? 'unknown';
                        $latestAttempt = $booking->supplierBookingAttempts->sortByDesc('created_at')->first();
                        $hasSuccess = $booking->supplierBookings->contains(fn ($item) => in_array($item->status, ['created', 'pending_ticketing', 'ticketed'], true));
                        $bookingRoute = $p === 'staff' ? route('staff.bookings.supplier-booking', $booking) : route('admin.bookings.supplier-booking', $booking);
                        $providerSupportsPnr = in_array((string) $provider, ['mock', 'duffel'], true);
                    @endphp
                    <p class="mb-1"><strong>Provider:</strong> {{ $provider }}</p>
                    <p class="mb-1 text-capitalize"><strong>Validation:</strong> {{ str_replace('_', ' ', (string) $validationStatus) }}</p>
                    <p class="mb-1 text-capitalize"><strong>Supplier booking status:</strong> {{ str_replace('_', ' ', (string) ($booking->supplier_booking_status ?? 'not started')) }}</p>
                    <p class="mb-1"><strong>PNR:</strong> {{ $booking->pnr ?? '—' }}</p>
                    <p class="mb-2"><strong>Supplier ref:</strong> {{ $booking->supplier_reference ?? '—' }}</p>
                    @if ($latestAttempt)
                        <p class="mb-1 text-capitalize"><strong>Latest attempt:</strong> {{ $latestAttempt->status }}</p>
                        <p class="mb-1"><strong>Attempted at:</strong> {{ $latestAttempt->attempted_at?->format('Y-m-d H:i') ?? '—' }}</p>
                        <p class="mb-1"><strong>Completed at:</strong> {{ $latestAttempt->completed_at?->format('Y-m-d H:i') ?? '—' }}</p>
                        @if ((string) $provider === 'duffel')
                            @php $attemptSummary = is_array($latestAttempt->safe_summary) ? $latestAttempt->safe_summary : []; @endphp
                            <p class="mb-1"><strong>Duffel order id:</strong> {{ $attemptSummary['duffel_order_id'] ?? $latestAttempt->supplier_reference ?? '—' }}</p>
                            <p class="mb-1"><strong>Booking reference:</strong> {{ $attemptSummary['booking_reference'] ?? '—' }}</p>
                            <p class="mb-1"><strong>Correlation id:</strong> {{ $attemptSummary['correlation_id'] ?? '—' }}</p>
                        @endif
                        @if ($latestAttempt->error_message)
                            <div class="alert alert-warning py-2 px-3 small">{{ $latestAttempt->error_message }}</div>
                        @endif
                    @endif
                    <p class="text-muted small">This does not issue a ticket. Ticketing remains a separate controlled step.</p>
                    @if ($providerSupportsPnr && ($supplierBookingEligible ?? false) && ! $hasSuccess)
                        <form method="post" action="{{ $bookingRoute }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">Create supplier booking / PNR</button>
                        </form>
                    @elseif (! $providerSupportsPnr)
                        <button type="button" class="btn btn-outline-secondary w-100" disabled>Create supplier booking / PNR</button>
                        <p class="text-muted small mt-2 mb-0">PNR creation for this provider requires API documentation review.</p>
                    @else
                        <button type="button" class="btn btn-outline-secondary w-100" disabled>Create supplier booking / PNR</button>
                    @endif
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Ticketing</h3></div>
                <div class="card-body">
                    @php
                        $latestTicketAttempt = $booking->ticketingAttempts->sortByDesc('created_at')->first();
                        $ticketRoute = $p === 'staff' ? route('staff.bookings.issue-ticket', $booking) : route('admin.bookings.issue-ticket', $booking);
                        $provider = (string) ($booking->latestSupplierBooking?->provider ?? $booking->supplier ?? '');
                        $providerSupported = in_array($provider, ['mock'], true);
                    @endphp
                    <p class="mb-1 text-capitalize"><strong>Payment status:</strong> {{ str_replace('_', ' ', (string) ($booking->payment_status ?? 'unpaid')) }}</p>
                    <p class="mb-1 text-capitalize"><strong>Supplier booking status:</strong> {{ str_replace('_', ' ', (string) ($booking->supplier_booking_status ?? 'not started')) }}</p>
                    <p class="mb-1"><strong>PNR:</strong> {{ $booking->pnr ?? '—' }}</p>
                    <p class="mb-1 text-capitalize"><strong>Ticketing status:</strong> {{ str_replace('_', ' ', (string) ($booking->ticketing_status ?? 'not started')) }}</p>
                    <p class="mb-2"><strong>Ticketed at:</strong> {{ $booking->ticketed_at?->format('Y-m-d H:i') ?? '—' }}</p>
                    <p class="text-muted small">Ticketing is a controlled action. This demo/mock workflow does not call Sabre/PIA ticketing APIs.</p>
                    @if ($providerSupported && ($ticketingEligible ?? false))
                        <form method="post" action="{{ $ticketRoute }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">Issue ticket</button>
                        </form>
                    @elseif (! $providerSupported)
                        <button type="button" class="btn btn-outline-secondary w-100" disabled>Issue ticket</button>
                        <p class="text-muted small mt-2 mb-0">Ticketing for this provider requires API documentation review.</p>
                    @else
                        <button type="button" class="btn btn-outline-secondary w-100" disabled>Issue ticket</button>
                    @endif
                    @if ($latestTicketAttempt)
                        <div class="mt-3 small">
                            <strong>Latest attempt:</strong> <span class="text-capitalize">{{ $latestTicketAttempt->status }}</span>
                            @if ($latestTicketAttempt->error_message)
                                <div class="alert alert-warning py-2 px-3 mt-2 mb-0">{{ $latestTicketAttempt->error_message }}</div>
                            @endif
                        </div>
                    @endif
                    @if ($booking->tickets->isNotEmpty())
                        <hr>
                        <div class="small text-secondary mb-1">Issued tickets</div>
                        @foreach ($booking->tickets as $ticket)
                            <div class="border rounded p-2 mb-2">
                                <div><strong>{{ $ticket->ticket_number ?? '—' }}</strong> · {{ $ticket->pnr ?? '—' }}</div>
                                <div class="small text-secondary">{{ $ticket->passenger?->first_name }} {{ $ticket->passenger?->last_name }} · {{ $ticket->airline_code ?? '—' }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Payments</h3></div>
                <div class="card-body">
                    @php
                        $paymentStoreUrl = $p === 'staff' ? route('staff.bookings.payments.store', $booking) : route('admin.bookings.payments.store', $booking);
                        $verifiedTotal = (float) ($booking->amount_paid ?? 0);
                        $totalDue = (float) ($booking->fareBreakdown?->total ?? 0);
                        $balanceDue = $booking->balance_due !== null ? (float) $booking->balance_due : max(0, $totalDue - $verifiedTotal);
                    @endphp
                    <p class="mb-1"><strong>Total:</strong> Rs {{ number_format($totalDue, 0) }}</p>
                    <p class="mb-1"><strong>Paid:</strong> Rs {{ number_format($verifiedTotal, 0) }}</p>
                    <p class="mb-2"><strong>Balance:</strong> Rs {{ number_format($balanceDue, 0) }}</p>
                    <p class="mb-2 text-capitalize"><strong>Status:</strong> {{ str_replace('_', ' ', (string) ($booking->payment_status ?? 'unpaid')) }}</p>
                    <div class="small text-secondary mb-2">Manual/offline payment records only. No gateway connected.</div>
                    <form method="post" action="{{ $paymentStoreUrl }}" class="mb-3">
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
                            <input name="amount" type="number" step="0.01" min="1" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Reference</label>
                            <input name="payment_reference" type="text" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">Record manual payment</button>
                    </form>
                    @foreach ($booking->payments->sortByDesc('created_at') as $payment)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>Rs {{ number_format((float) $payment->amount, 0) }}</strong>
                                <span class="text-capitalize">{{ str_replace('_', ' ', $payment->status->value) }}</span>
                            </div>
                            <div class="small text-secondary">{{ str_replace('_', ' ', $payment->method->value) }} · {{ $payment->payment_reference ?? 'No ref' }}</div>
                            @if ($payment->status->value === 'submitted')
                                @php
                                    $verifyUrl = $p === 'staff' ? route('staff.bookings.payments.verify', $payment) : route('admin.bookings.payments.verify', $payment);
                                    $rejectUrl = $p === 'staff' ? route('staff.bookings.payments.reject', $payment) : route('admin.bookings.payments.reject', $payment);
                                @endphp
                                <form method="post" action="{{ $verifyUrl }}" class="mt-2">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success">Verify</button>
                                </form>
                                <form method="post" action="{{ $rejectUrl }}" class="mt-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="reason" value="Rejected during admin review">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            @endif
                            @if ($payment->status->value === 'verified')
                                <form method="post" action="{{ route($docReceiptRoute, $payment) }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Generate receipt</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Cancellation &amp; Refund</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Booking status:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', $booking->status->value) }}</span></p>
                    <p class="mb-1"><strong>Cancellation status:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', (string) ($booking->cancellation_status ?? 'none')) }}</span></p>
                    <p class="mb-2"><strong>Refund status:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', (string) ($booking->refund_status ?? 'none')) }}</span></p>
                    @if ($booking->status->value === 'ticketed')
                        <div class="alert alert-warning py-2 px-3 small">
                            Ticketed bookings require manual supplier void/refund handling until API docs are reviewed.
                        </div>
                    @endif
                    <div class="alert alert-secondary py-2 px-3 small">
                        Refund records are manual only and do not trigger gateway/bank transfers.
                    </div>

                    <form method="post" action="{{ $cancelStoreUrl }}" class="mb-3 border rounded p-2">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Request cancellation</label>
                            <select name="cancellation_type" class="form-select" required>
                                @foreach (['booking_cancel', 'ticket_void', 'ticket_refund', 'supplier_cancel'] as $type)
                                    <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea name="reason" class="form-control" rows="2" placeholder="Reason (optional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">Submit cancellation request</button>
                    </form>

                    @php $latestCancellation = $booking->cancellationRequests->sortByDesc('created_at')->first(); @endphp
                    @if ($latestCancellation)
                        <div class="border rounded p-2 mb-3 small">
                            <div><strong>Latest:</strong> {{ $latestCancellation->id }} · <span class="text-capitalize">{{ $latestCancellation->status->value }}</span></div>
                            <div><strong>Source:</strong> {{ $latestCancellation->request_source }}</div>
                            <div><strong>Type:</strong> {{ $latestCancellation->cancellation_type->value }}</div>
                            <div><strong>Reason:</strong> {{ $latestCancellation->reason ?? 'N/A' }}</div>
                            @if (in_array($latestCancellation->status->value, ['requested', 'approved']))
                                @php
                                    $approveUrl = $p === 'staff' ? route('staff.bookings.cancellations.approve', $latestCancellation) : route('admin.bookings.cancellations.approve', $latestCancellation);
                                    $rejectUrl = $p === 'staff' ? route('staff.bookings.cancellations.reject', $latestCancellation) : route('admin.bookings.cancellations.reject', $latestCancellation);
                                    $processUrl = $p === 'staff' ? route('staff.bookings.cancellations.process', $latestCancellation) : route('admin.bookings.cancellations.process', $latestCancellation);
                                @endphp
                                <form method="post" action="{{ $approveUrl }}" class="mt-2">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="post" action="{{ $processUrl }}" class="mt-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-primary">Process</button>
                                </form>
                                <form method="post" action="{{ $rejectUrl }}" class="mt-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="reason" value="Rejected by operations review">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            @endif
                        </div>
                    @endif

                    <div class="small text-secondary mb-1">Cancellation history</div>
                    @forelse ($booking->cancellationRequests->sortByDesc('created_at')->take(5) as $cancel)
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between">
                                <strong>#{{ $cancel->id }}</strong>
                                <span class="text-capitalize">{{ str_replace('_', ' ', $cancel->status->value) }}</span>
                            </div>
                            <div>{{ $cancel->request_source }} · {{ $cancel->cancellation_type->value }}</div>
                            <div class="text-secondary">{{ $cancel->created_at?->format('Y-m-d H:i') }}</div>
                        </div>
                    @empty
                        <div class="text-secondary small mb-3">No cancellation requests yet.</div>
                    @endforelse

                    <form method="post" action="{{ $refundStoreUrl }}" class="mb-3 border rounded p-2">
                        @csrf
                        <div class="mb-2"><label class="form-label">Create refund record</label></div>
                        <div class="mb-2">
                            <input class="form-control" type="number" name="amount" step="0.01" min="1" placeholder="Amount" required>
                        </div>
                        <div class="mb-2">
                            <select name="method" class="form-select" required>
                                @foreach (['bank_transfer', 'cash', 'card_manual', 'easypaisa', 'jazzcash', 'other'] as $method)
                                    <option value="{{ $method }}">{{ str_replace('_', ' ', $method) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2"><input class="form-control" type="text" name="reference" placeholder="Reference"></div>
                        <div class="mb-2"><textarea class="form-control" name="notes" rows="2" placeholder="Notes"></textarea></div>
                        <button type="submit" class="btn btn-outline-primary w-100">Create refund</button>
                    </form>

                    <div class="small text-secondary mb-1">Refund records</div>
                    @forelse ($booking->refunds->sortByDesc('created_at')->take(8) as $refund)
                        @php
                            $refundApproveUrl = $p === 'staff' ? route('staff.bookings.refunds.approve', $refund) : route('admin.bookings.refunds.approve', $refund);
                            $refundPaidUrl = $p === 'staff' ? route('staff.bookings.refunds.mark-paid', $refund) : route('admin.bookings.refunds.mark-paid', $refund);
                            $refundRejectUrl = $p === 'staff' ? route('staff.bookings.refunds.reject', $refund) : route('admin.bookings.refunds.reject', $refund);
                        @endphp
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between">
                                <strong>Rs {{ number_format((float) $refund->amount, 0) }}</strong>
                                <span class="text-capitalize">{{ $refund->status->value }}</span>
                            </div>
                            <div>{{ str_replace('_', ' ', $refund->method) }} · {{ $refund->reference ?? 'No ref' }}</div>
                            <div class="text-secondary">{{ $refund->created_at?->format('Y-m-d H:i') }}</div>
                            @if (in_array($refund->status->value, ['pending', 'approved']))
                                <form method="post" action="{{ $refundApproveUrl }}" class="mt-1">@csrf @method('PATCH')<button type="submit" class="btn btn-sm btn-success">Approve</button></form>
                                <form method="post" action="{{ $refundPaidUrl }}" class="mt-1">@csrf @method('PATCH')<button type="submit" class="btn btn-sm btn-primary">Mark paid</button></form>
                                <form method="post" action="{{ $refundRejectUrl }}" class="mt-1">@csrf @method('PATCH')<input type="hidden" name="reason" value="Rejected by operations"><button type="submit" class="btn btn-sm btn-outline-danger">Reject</button></form>
                            @endif
                        </div>
                    @empty
                        <div class="text-secondary small">No refunds yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Documents</h3></div>
                <div class="card-body">
                    <div class="d-grid gap-2 mb-3">
                        <form method="post" action="{{ $docConfirmationUrl }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">Generate booking confirmation</button>
                        </form>
                        <form method="post" action="{{ $docInvoiceUrl }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">Generate invoice</button>
                        </form>
                        <form method="post" action="{{ $docItineraryUrl }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled($booking->tickets->isEmpty())>Generate ticket itinerary</button>
                        </form>
                    </div>
                    @forelse ($booking->documents->sortByDesc('created_at') as $document)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ str_replace('_', ' ', $document->document_type->value) }}</strong>
                                <span class="text-capitalize">{{ $document->status->value }}</span>
                            </div>
                            <div class="small text-secondary">{{ $document->document_number ?? 'N/A' }} · {{ $document->generated_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            @if ($document->status->value === 'generated' && $document->file_path)
                                <a class="btn btn-sm btn-outline-secondary mt-2" href="{{ route($docDownloadRoute, $document) }}">Download</a>
                            @endif
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">No documents generated yet.</p>
                    @endforelse
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Communication</h3></div>
                <div class="card-body">
                    @forelse ($booking->communicationLogs->sortByDesc('created_at')->take(10) as $comm)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $comm->event }}</strong>
                                <span class="text-capitalize">{{ $comm->status }}</span>
                            </div>
                            <div class="small text-secondary">{{ strtoupper($comm->channel) }} · {{ $comm->recipient_email ?? $comm->recipient_phone ?? 'N/A' }}</div>
                            <div class="small text-secondary">Sent: {{ $comm->sent_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            @if ($comm->error_message)
                                <div class="small text-danger mt-1">{{ $comm->error_message }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">No communication logs yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Change status</h3></div>
                <div class="card-body">
                    @if (count($allowedTransitions) > 0)
                        <form method="post" action="{{ $statusUrl }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-2">
                                <label class="form-label">New status</label>
                                <select name="status" class="form-select" required>
                                    @foreach ($allowedTransitions as $st)
                                        <option value="{{ $st->value }}">{{ str_replace('_', ' ', $st->value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Note (optional)</label>
                                <textarea name="note" class="form-control" rows="2" maxlength="1000"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update status</button>
                        </form>
                    @else
                        <p class="text-secondary small mb-0">No transitions available (terminal state or insufficient permissions).</p>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Add note</h3></div>
                <div class="card-body">
                    <form method="post" action="{{ $noteUrl }}">
                        @csrf
                        <div class="mb-2">
                            <textarea name="note" class="form-control" rows="3" required maxlength="10000" placeholder="Internal note…"></textarea>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" name="is_customer_visible" id="custvis">
                            <label class="form-check-label" for="custvis">Customer visible</label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">Save note</button>
                    </form>
                </div>
            </div>

            @if ($p === 'admin' && $assignUrl)
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Assign staff</h3></div>
                    <div class="card-body">
                        @if ($assignableStaff->isEmpty())
                            <p class="text-secondary small mb-0">No assignable users in this agency.</p>
                        @else
                            <form method="post" action="{{ $assignUrl }}">
                                @csrf
                                @method('PATCH')
                                <select name="staff_user_id" class="form-select mb-2">
                                    <option value="">— Unassign —</option>
                                    @foreach ($assignableStaff as $su)
                                        <option value="{{ $su->id }}" @selected($booking->assigned_staff_id === $su->id)>{{ $su->name }} ({{ $su->email }})</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-secondary w-100">Save assignment</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
