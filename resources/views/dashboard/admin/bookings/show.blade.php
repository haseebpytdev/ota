@extends('layouts.dashboard')

@php
    use App\Http\Controllers\Admin\BookingManagementController;
    use App\Support\Bookings\BookingOperationalStatus;
    use App\Support\Bookings\DocumentOperationalState;
    use App\Support\Bookings\PaymentOperationalStatus;
    use App\Support\Bookings\SupplierOperationalStatus;
    use App\Support\Bookings\TicketingOperationalStatus;
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
    $communicationSendUrl = $p === 'admin' ? route('admin.bookings.communication.send', $booking) : null;
@endphp

@section('title', 'Booking '.$booking->booking_reference ?: '#'.$booking->id)

@push('styles')
<style>
    .booking-detail .card { border: 1px solid rgba(98,105,118,.16); }
    .booking-detail h3 { font-size: 1rem; font-weight: 600; }
    .booking-command-header { border: 1px solid rgba(59,130,246,.22); box-shadow: 0 6px 24px rgba(30,64,175,.08); }
    .booking-command-top { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .65rem; }
    .booking-command-ref { font-size: 1.08rem; font-weight: 700; color: #0f172a; }
    .booking-command-pill { display: inline-flex; align-items: center; font-size: .74rem; font-weight: 700; padding: .22rem .52rem; border-radius: 999px; border: 1px solid rgba(148,163,184,.4); background: #f8fafc; color: #334155; }
    .booking-command-meta { color: #475569; font-size: .9rem; margin-top: .35rem; }
    .booking-command-amounts { color: #0f172a; font-size: .9rem; font-weight: 600; margin-top: .22rem; }
    .booking-quick-actions { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .55rem; margin-top: .9rem; }
    .booking-quick-action { border: 1px solid rgba(148,163,184,.3); border-radius: .55rem; background: #fff; padding: .5rem; }
    .booking-quick-action .btn { width: 100%; }
    .booking-quick-action-reason { font-size: .72rem; color: #64748b; margin-top: .32rem; line-height: 1.35; }
    .booking-tabs-wrap { margin-bottom: 1rem; }
    .booking-tabs-wrap .nav-link { font-size: .82rem; font-weight: 600; border-radius: 999px; padding: .38rem .72rem; color: #475569; }
    .booking-tabs-wrap .nav-link.active { background: #e0edff; color: #1d4ed8; border-color: #93c5fd; }
    .booking-tab-hidden { display: none !important; }
    .overview-summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem .9rem; }
    .overview-kv { display: flex; justify-content: space-between; gap: .75rem; font-size: .86rem; border-bottom: 1px dashed rgba(148,163,184,.3); padding-bottom: .22rem; }
    .overview-kv:last-child { border-bottom: 0; }
    .overview-kv .label { color: #64748b; font-weight: 600; }
    .overview-kv .value { color: #0f172a; font-weight: 700; text-align: right; }
    .lifecycle-track { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .35rem; margin-top: .6rem; }
    .lifecycle-step { text-align: center; font-size: .72rem; padding: .28rem .2rem; border-radius: 999px; border: 1px solid #cbd5e1; color: #64748b; background: #f8fafc; }
    .lifecycle-step.is-done { border-color: #93c5fd; color: #1d4ed8; background: #e0edff; }
    .passenger-item { border: 1px solid rgba(148,163,184,.28); border-radius: .6rem; padding: .7rem .75rem; margin-bottom: .65rem; }
    .passenger-item:last-child { margin-bottom: 0; }
    .passenger-head { display: flex; flex-wrap: wrap; gap: .35rem .45rem; align-items: center; margin-bottom: .45rem; }
    .passenger-name { font-size: .92rem; font-weight: 700; color: #0f172a; }
    .passenger-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .35rem .9rem; }
    .passenger-kv { display: flex; justify-content: space-between; gap: .7rem; border-bottom: 1px dashed rgba(148,163,184,.25); padding-bottom: .15rem; font-size: .82rem; }
    .passenger-kv .label { color: #64748b; font-weight: 600; }
    .passenger-kv .value { color: #0f172a; font-weight: 600; text-align: right; word-break: break-word; }
    .timeline-entry { border-left: 2px solid var(--tblr-primary, #206bc4); padding-left: 1rem; margin-bottom: 1rem; }
    .audit-row { font-size: .8125rem; border-bottom: 1px dashed rgba(98,105,118,.15); padding: .35rem 0; }
    @media (max-width: 767px) {
        .booking-quick-actions { grid-template-columns: 1fr; }
        .overview-summary-grid { grid-template-columns: 1fr; }
        .lifecycle-track { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .passenger-grid { grid-template-columns: 1fr; }
        .booking-detail .card-body .btn,
        .booking-detail .card-body .form-select,
        .booking-detail .card-body .form-control {
            width: 100%;
        }
        .booking-detail .card-body .btn-sm {
            padding-top: .45rem;
            padding-bottom: .45rem;
        }
        .booking-detail .badge {
            white-space: normal;
        }
    }
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

    @php
        $pipelineBooking = str_replace('_', ' ', $booking->status->value);
        $operationalStatus = BookingOperationalStatus::fromValues(
            $booking->status->value,
            (string) ($booking->payment_status ?? ''),
            (string) ($booking->supplier_booking_status ?? ''),
            (string) ($booking->ticketing_status ?? ''),
            ((string) ($booking->pnr ?? '')) !== '',
            (string) ($booking->cancellation_status ?? '')
        );
        $paymentOperational = PaymentOperationalStatus::fromValue((string) ($booking->payment_status ?? 'unpaid'));
        $pipelinePayment = $paymentOperational['label'];
        $supplierOperational = SupplierOperationalStatus::fromValues(
            (string) ($booking->supplier_booking_status ?? 'not_started'),
            (string) (($booking->meta['supplier_provider'] ?? null) ?: ($booking->latestSupplierBooking?->provider ?? $booking->supplier ?? '')),
            ((string) ($booking->pnr ?? '')) !== ''
        );
        $pipelineSupplier = $supplierOperational['label'];
        $ticketingOperational = TicketingOperationalStatus::fromValues(
            (string) ($booking->ticketing_status ?? 'not_started'),
            (string) ($booking->payment_status ?? 'unpaid'),
            ((string) ($booking->pnr ?? '')) !== '',
            $booking->tickets->isNotEmpty(),
            (string) (($booking->meta['supplier_provider'] ?? null) ?: ($booking->latestSupplierBooking?->provider ?? $booking->supplier ?? '')),
            (string) ($booking->cancellation_status ?? '')
        );
        $pipelineTicket = $ticketingOperational['label'];
        $bookingRef = $booking->booking_reference ?: 'Draft #'.$booking->id;
        $travelDateLabel = $booking->travel_date?->format('d M Y') ?? '—';
        $paxCount = $booking->passengers->count();
        $totalDue = (float) ($booking->fareBreakdown?->total ?? 0);
        $paidAmount = (float) ($booking->amount_paid ?? 0);
        $balanceAmount = $booking->balance_due !== null ? (float) $booking->balance_due : max(0, $totalDue - $paidAmount);
        $paymentStoreUrl = $p === 'staff' ? route('staff.bookings.payments.store', $booking) : route('admin.bookings.payments.store', $booking);
        $bookingRoute = $p === 'staff' ? route('staff.bookings.supplier-booking', $booking) : route('admin.bookings.supplier-booking', $booking);
        $ticketRoute = $p === 'staff' ? route('staff.bookings.issue-ticket', $booking) : route('admin.bookings.issue-ticket', $booking);
        $provider = (string) (($booking->meta['supplier_provider'] ?? null) ?: ($booking->latestSupplierBooking?->provider ?? $booking->supplier ?? ''));
        $providerSupportsPnr = in_array($provider, ['duffel', 'sabre', 'pia', 'airline_direct', 'amadeus', 'travelport'], true);
        $providerSupportsTicketing = in_array($provider, ['sabre', 'pia', 'airline_direct'], true);
        $hasSupplierPnr = ((string) ($booking->pnr ?? '')) !== '';
        $hasSupplierBooking = $booking->supplierBookings->contains(fn ($item) => in_array($item->status, ['created', 'pending_ticketing', 'ticketed'], true));
        $verifiedPayment = $booking->payments->firstWhere('status.value', 'verified');
        $isPaidForActions = (string) ($booking->payment_status ?? 'unpaid') === 'paid';
        $isTicketedForActions = $booking->tickets->isNotEmpty() || in_array((string) ($booking->ticketing_status ?? ''), ['ticketed', 'issued'], true);
        $canCreateSupplierBooking = $providerSupportsPnr && ($supplierBookingEligible ?? false) && ! $hasSupplierBooking;
        $canIssueTicket = $providerSupportsTicketing && ($ticketingEligible ?? false);
        $canGenerateItinerary = $booking->tickets->isNotEmpty();
        $canCreatePnrNow = $canCreateSupplierBooking && $isPaidForActions && ! $isTicketedForActions;
        $canIssueTicketNow = $canIssueTicket && $isPaidForActions && $hasSupplierPnr && ! $isTicketedForActions;
        $supplierReason = !$providerSupportsPnr
            ? 'Reason: Supplier provider does not support automated PNR creation yet.'
            : (($supplierBookingEligible ?? false) ? ($hasSupplierBooking ? 'Reason: Supplier booking already exists for this booking.' : '') : 'Reason: Offer validation and booking prerequisites are not complete.');
        $ticketReason = !$providerSupportsTicketing
            ? 'Reason: Ticketing for this provider is not integrated yet.'
            : (($ticketingEligible ?? false) ? '' : 'Reason: Payment must be verified and supplier PNR must exist.');
        $itineraryReason = $canGenerateItinerary ? '' : 'Reason: No issued ticket found yet.';
        $leadPax = $booking->passengers->firstWhere('is_lead_passenger', true) ?? $booking->passengers->sortBy('passenger_index')->first();
        $leadPaxName = $leadPax ? trim(implode(' ', array_filter([$leadPax->title, $leadPax->first_name, $leadPax->last_name]))) : '—';
        $contactLine = $booking->contact ? (($booking->contact->phone ?? '—').' / '.($booking->contact->email ?? '—')) : '—';
        $hasContact = $booking->contact !== null && (((string) ($booking->contact->email ?? '')) !== '' || ((string) ($booking->contact->phone ?? '')) !== '');
        $isCancelledOrRefunded = in_array((string) $booking->status->value, ['cancelled'], true)
            || in_array((string) ($booking->refund_status ?? ''), ['refunded'], true);
        $hasFareSnapshot = $booking->fareBreakdown !== null;
        $offerValid = in_array((string) (($booking->meta['offer_validation_status'] ?? 'unknown')), ['valid', 'validated', 'ok', 'pass'], true);
        $adminOverrideAllowed = $p === 'admin';
        $alreadyTicketed = $booking->tickets->isNotEmpty() || in_array((string) ($booking->ticketing_status ?? ''), ['ticketed', 'issued'], true);
        $canRecordPaymentAction = ! $isCancelledOrRefunded;
        $canGenerateInvoiceAction = $hasFareSnapshot;
        $canCreatePnrAction = ($isPaidForActions || $adminOverrideAllowed) && $offerValid && $providerSupportsPnr && ($supplierBookingEligible ?? false);
        $canIssueTicketAction = $isPaidForActions && $hasSupplierPnr && ! $alreadyTicketed && $providerSupportsTicketing && ($ticketingEligible ?? false);
        $canGenerateItineraryAction = $canGenerateItinerary;
        $canSendUpdateAction = $hasContact;
        $canAssignStaffAction = $assignUrl !== null && $p === 'admin';
        $canChangeStatusAction = count($allowedTransitions) > 0;
        $canAddNoteAction = true;
        $hasUnpaidOrPartialBalance = in_array((string) ($booking->payment_status ?? 'unpaid'), ['unpaid', 'partial'], true)
            || (float) ($booking->balance_due ?? 0) > 0;
        $hasInvoiceDocument = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'invoice');
        $hasReceiptDocument = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'payment_receipt');
        $hasItineraryDocument = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'ticket_itinerary') || $booking->tickets->isNotEmpty();
        $hasCancellationDocument = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'cancellation_confirmation')
            || $booking->cancellationRequests->isNotEmpty();
        $hasRefundDocument = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'refund_note')
            || $booking->refunds->isNotEmpty();
        $nextActionLabel = $canCreateSupplierBooking
            ? 'Create supplier booking / PNR'
            : ($canIssueTicket
                ? 'Issue ticket'
                : (($booking->payment_status ?? 'unpaid') !== 'paid' ? 'Record payment and verify it' : 'Review booking and update status'));
        $actionState = $actionState ?? [
            'next_action' => $nextActionLabel,
            'enabled_actions' => [],
            'disabled_actions' => [],
            'disabled_reasons' => [],
            'workflow_step_statuses' => [],
        ];
        $stateEnabled = is_array($actionState['enabled_actions'] ?? null) ? $actionState['enabled_actions'] : [];
        $stateDisabledReasons = is_array($actionState['disabled_reasons'] ?? null) ? $actionState['disabled_reasons'] : [];
        $stateWorkflow = is_array($actionState['workflow_step_statuses'] ?? null) ? $actionState['workflow_step_statuses'] : [];
        $nextActionText = (string) ($actionState['next_action'] ?? $nextActionLabel);
        $overviewPrimaryActionLabel = 'Open full record';
        $overviewPrimaryActionUrl = route('admin.bookings.show', $booking);
        if (str_contains(strtolower($nextActionText), 'payment')) {
            $overviewPrimaryActionLabel = 'Record payment';
            $overviewPrimaryActionUrl = '#payments';
        } elseif (str_contains(strtolower($nextActionText), 'supplier')) {
            $overviewPrimaryActionLabel = 'Create supplier booking / PNR';
            $overviewPrimaryActionUrl = '#supplier';
        } elseif (str_contains(strtolower($nextActionText), 'ticket')) {
            $overviewPrimaryActionLabel = 'Issue ticket';
            $overviewPrimaryActionUrl = '#ticketing';
        } elseif (str_contains(strtolower($nextActionText), 'itinerary')) {
            $overviewPrimaryActionLabel = 'Generate itinerary';
            $overviewPrimaryActionUrl = '#ticketing';
        }
    @endphp
    <div class="card mb-3 booking-command-header">
        <div class="card-body">
            <div class="booking-command-top">
                <span class="booking-command-ref">{{ $bookingRef }}</span>
                <span class="booking-command-pill">{{ ucwords($operationalStatus['label']) }}</span>
                <span class="booking-command-pill">Payment: {{ ucwords($pipelinePayment) }}</span>
                <span class="booking-command-pill">PNR: {{ ucwords($pipelineSupplier) }}</span>
                <span class="booking-command-pill">Ticketing: {{ ucwords($pipelineTicket) }}</span>
            </div>
            <div class="booking-command-meta">
                {{ $booking->route ?? '—' }} · {{ $booking->airline ?? '—' }} · {{ $travelDateLabel }} · {{ $paxCount }} passenger{{ $paxCount === 1 ? '' : 's' }}
            </div>
            <div class="booking-command-amounts">
                Total: Rs {{ number_format($totalDue, 0) }} · Balance: Rs {{ number_format($balanceAmount, 0) }}
            </div>
            <div class="booking-command-meta">
                Paid: Rs {{ number_format($paidAmount, 0) }} · Lead passenger: {{ $leadPaxName }} · Contact: {{ $contactLine }} · Assigned: {{ $booking->assignedStaff?->name ?? 'Unassigned' }}
            </div>

            <div class="booking-quick-actions">
                <div class="booking-quick-action">
                    <a href="{{ $overviewPrimaryActionUrl }}" class="btn btn-primary btn-sm">{{ $overviewPrimaryActionLabel }}</a>
                </div>
                <div class="booking-quick-action">
                    <form method="post" action="{{ $docInvoiceUrl }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm" @disabled(! $canGenerateInvoiceAction || ! in_array('generate_invoice', $stateEnabled, true))>Generate invoice</button>
                    </form>
                </div>
                <div class="booking-quick-action">
                    <a href="{{ route('admin.bookings.show', $booking) }}?tab=communication#add-note-panel" class="btn btn-outline-primary btn-sm">Add note</a>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3 ota-admin-booking-pipeline" data-booking-pipeline-bar>
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center gap-3 justify-content-between">
                <div class="fw-semibold text-secondary small text-uppercase mb-0">Booking status</div>
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <span class="badge bg-blue-lt text-blue">Booking · {{ $pipelineBooking }}</span>
                    <span class="badge bg-azure-lt text-azure">Payment · {{ $pipelinePayment }}</span>
                    <span class="badge bg-purple-lt text-purple">Supplier / PNR · {{ $pipelineSupplier }}</span>
                    <span class="badge bg-teal-lt text-teal">Ticketing · {{ $pipelineTicket }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="booking-tabs-wrap">
        <ul class="nav nav-pills flex-wrap gap-2" id="booking-detail-tabs" data-booking-tabs>
            <li class="nav-item"><button type="button" class="nav-link active" data-tab-target="overview">Overview</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab-target="passengers">Passengers</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab-target="payments">Payments</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab-target="supplier">Supplier/PNR</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab-target="ticketing">Ticketing</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab-target="documents">Documents</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab-target="refunds">Refunds</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab-target="communication">Communication</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab-target="audit">Audit / Timeline</button></li>
        </ul>
    </div>

    <div class="row g-4 booking-detail" data-booking-tab-container>
        <div class="col-lg-8">
            <div class="card mb-3" data-tab-section="overview">
                <div class="card-header"><h3 class="card-title mb-0">Operational summary</h3></div>
                <div class="card-body">
                    <div class="overview-summary-grid">
                        <div class="overview-kv"><span class="label">Booking status</span><span class="value text-capitalize">{{ $pipelineBooking }}</span></div>
                        <div class="overview-kv"><span class="label">Operational status</span><span class="value text-capitalize">{{ $operationalStatus['label'] }}</span></div>
                        <div class="overview-kv"><span class="label">Status meaning</span><span class="value">{{ $operationalStatus['meaning'] }}</span></div>
                        <div class="overview-kv"><span class="label">Payment status</span><span class="value text-capitalize">{{ $pipelinePayment }}</span></div>
                        <div class="overview-kv"><span class="label">Payment meaning</span><span class="value">{{ $paymentOperational['meaning'] }}</span></div>
                        <div class="overview-kv"><span class="label">Supplier status</span><span class="value text-capitalize">{{ $pipelineSupplier }}</span></div>
                        <div class="overview-kv"><span class="label">Supplier meaning</span><span class="value">{{ $supplierOperational['meaning'] }}</span></div>
                        <div class="overview-kv"><span class="label">Ticketing status</span><span class="value text-capitalize">{{ $pipelineTicket }}</span></div>
                        <div class="overview-kv"><span class="label">Ticketing meaning</span><span class="value">{{ $ticketingOperational['meaning'] }}</span></div>
                        <div class="overview-kv"><span class="label">Route</span><span class="value">{{ $booking->route ?? '—' }}</span></div>
                        <div class="overview-kv"><span class="label">Fare</span><span class="value">Rs {{ number_format($totalDue, 0) }}</span></div>
                        <div class="overview-kv"><span class="label">Passenger count</span><span class="value">{{ $paxCount }}</span></div>
                        <div class="overview-kv"><span class="label">Lead passenger</span><span class="value">{{ $leadPaxName }}</span></div>
                        <div class="overview-kv"><span class="label">Contact</span><span class="value">{{ $contactLine }}</span></div>
                        <div class="overview-kv"><span class="label">Assigned staff</span><span class="value">{{ $booking->assignedStaff?->name ?? 'Unassigned' }}</span></div>
                        <div class="overview-kv"><span class="label">Next recommended action</span><span class="value">{{ $actionState['next_action'] ?? $nextActionLabel }}</span></div>
                    </div>
                    @if ($stateWorkflow !== [])
                        <div class="small text-secondary mt-2">
                            @foreach ($stateWorkflow as $step => $status)
                                <div><strong>{{ str_replace('_', ' ', (string) $step) }}:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', (string) $status) }}</span></div>
                            @endforeach
                        </div>
                    @endif
                    @php
                        $hasLeadContact = $booking->contact !== null && ((string) ($booking->contact->email ?? '') !== '' || (string) ($booking->contact->phone ?? '') !== '');
                        $hasPaxDetails = $booking->passengers->isNotEmpty() && $hasLeadContact;
                        $offerValidated = in_array((string) (($booking->meta['offer_validation_status'] ?? 'unknown')), ['valid', 'validated', 'ok', 'pass'], true);
                        $invoiceGenerated = $booking->documents->contains(fn ($d) => (string) $d->document_type->value === 'invoice');
                        $paymentRecorded = $booking->payments->isNotEmpty();
                        $paymentVerified = in_array((string) ($booking->payment_status ?? 'unpaid'), ['paid', 'partial'], true);
                        $pnrCreated = ((string) ($booking->supplier_booking_status ?? 'not_started') !== 'not_started') || ((string) ($booking->pnr ?? '') !== '');
                        $ticketIssued = in_array((string) ($booking->ticketing_status ?? 'not_started'), ['ticketed', 'issued', 'completed'], true) || $booking->tickets->isNotEmpty();
                        $documentsGenerated = $booking->documents->isNotEmpty();
                        $customerNotified = $booking->communicationLogs->contains(function ($log) {
                            $status = strtolower((string) ($log->status ?? ''));
                            return ($status === 'sent' || $status === 'delivered' || $status === 'success')
                                && (!empty($log->recipient_email) || !empty($log->recipient_phone));
                        });
                        $postBookingSupport = ((string) ($booking->cancellation_status ?? 'none') !== 'none')
                            || ((string) ($booking->refund_status ?? 'none') !== 'none')
                            || $booking->cancellationRequests->isNotEmpty()
                            || $booking->refunds->isNotEmpty();
                        $bookingClosed = in_array((string) $booking->status->value, ['completed', 'closed', 'ticketed', 'cancelled', 'refunded'], true);
                        $lifecycleDone = [
                            'step_1_request_created' => true,
                            'step_2_pax_contact_captured' => $hasPaxDetails,
                            'step_3_fare_offer_validated' => $offerValidated,
                            'step_4_invoice_generated' => $invoiceGenerated,
                            'step_5_payment_submitted_or_recorded' => $paymentRecorded,
                            'step_6_payment_verified' => $paymentVerified,
                            'step_7_pnr_created' => $pnrCreated,
                            'step_8_ticket_issued' => $ticketIssued,
                            'step_9_docs_generated' => $documentsGenerated,
                            'step_10_customer_notified' => $customerNotified,
                            'step_11_post_booking_support' => $postBookingSupport,
                            'step_12_booking_closed' => $bookingClosed,
                        ];
                    @endphp
                    <div class="small text-secondary fw-semibold mt-3">Lifecycle progress</div>
                    <div class="lifecycle-track">
                        <div class="lifecycle-step {{ $lifecycleDone['step_1_request_created'] ? 'is-done' : '' }}">1. Request/draft</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_2_pax_contact_captured'] ? 'is-done' : '' }}">2. Pax/contact</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_3_fare_offer_validated'] ? 'is-done' : '' }}">3. Fare/offer</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_4_invoice_generated'] ? 'is-done' : '' }}">4. Invoice/pay req</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_5_payment_submitted_or_recorded'] ? 'is-done' : '' }}">5. Payment submit</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_6_payment_verified'] ? 'is-done' : '' }}">6. Payment verify</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_7_pnr_created'] ? 'is-done' : '' }}">7. Supplier/PNR</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_8_ticket_issued'] ? 'is-done' : '' }}">8. Ticket issued</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_9_docs_generated'] ? 'is-done' : '' }}">9. Documents</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_10_customer_notified'] ? 'is-done' : '' }}">10. Notified</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_11_post_booking_support'] ? 'is-done' : '' }}">11. Support</div>
                        <div class="lifecycle-step {{ $lifecycleDone['step_12_booking_closed'] ? 'is-done' : '' }}">12. Closed</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3" data-tab-section="passengers">
                <div class="card-header"><h3 class="card-title mb-0">Passengers &amp; contact</h3></div>
                <div class="card-body">
                    @php
                        $hasPassengers = $booking->passengers->isNotEmpty();
                        $isTicketedForPassengerOps = $booking->tickets->isNotEmpty() || in_array((string) ($booking->ticketing_status ?? ''), ['ticketed', 'issued'], true);
                        $canEditPassengerDetails = ! $isTicketedForPassengerOps || $p === 'admin';
                        $canValidatePassengerData = $hasPassengers;
                        $canAddPassengerNote = in_array($p, ['admin', 'staff'], true);
                    @endphp
                    <h4 class="mb-2">Actions</h4>
                    <div class="d-grid gap-2 mb-3">
                        @if ($canEditPassengerDetails)
                            <button type="button" class="btn btn-outline-primary w-100" disabled>Edit passenger details</button>
                            <div class="small text-secondary">Passenger edit action is visible here and will be enabled when edit workflow endpoint is connected.</div>
                        @else
                            <button type="button" class="btn btn-outline-secondary w-100" disabled>Edit passenger details</button>
                            <div class="small text-secondary">Reason: Ticket already issued.</div>
                        @endif

                        @if ($canValidatePassengerData)
                            <button type="button" class="btn btn-outline-primary w-100" disabled>Validate passenger data</button>
                            <div class="small text-secondary">Validation checklist is available in this tab; submit action can be wired to validation service.</div>
                        @else
                            <button type="button" class="btn btn-outline-secondary w-100" disabled>Validate passenger data</button>
                            <div class="small text-secondary">Reason: No passengers.</div>
                        @endif

                        <button type="button" class="btn btn-outline-secondary w-100" disabled>Mark lead passenger</button>
                        <div class="small text-secondary">Lead must be adult.</div>

                        @if ($canAddPassengerNote)
                            <a href="{{ route('admin.bookings.show', $booking) }}?tab=communication#add-note-panel" class="btn btn-outline-primary w-100">Add internal passenger note</a>
                        @else
                            <button type="button" class="btn btn-outline-secondary w-100" disabled>Add internal passenger note</button>
                            <div class="small text-secondary">Reason: Permission denied.</div>
                        @endif
                    </div>

                    <h4 class="mb-2">Passenger records</h4>
                    @foreach ($booking->passengers->sortBy('passenger_index')->values() as $index => $pax)
                        <div class="passenger-item">
                            <div class="passenger-head">
                                <span class="passenger-name">Passenger {{ $index + 1 }}</span>
                                <span class="badge bg-secondary-lt text-capitalize">{{ $pax->passenger_type ?? 'adult' }}</span>
                                @if($pax->is_lead_passenger)
                                    <span class="badge bg-info-lt">Lead passenger</span>
                                @endif
                                <span class="badge bg-danger-lt">Sensitive</span>
                            </div>
                            <div class="passenger-grid">
                                <div class="passenger-kv"><span class="label">Name</span><span class="value">{{ trim(($pax->title.' '.$pax->first_name.' '.$pax->last_name)) }}</span></div>
                                <div class="passenger-kv"><span class="label">DOB</span><span class="value">{{ $pax->date_of_birth?->format('Y-m-d') ?? '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Gender</span><span class="value">{{ $pax->gender ?: '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Nationality</span><span class="value">{{ $pax->nationality ? strtoupper($pax->nationality) : '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Passport</span><span class="value">{{ $pax->passport_number ?: '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Passport expiry</span><span class="value">{{ $pax->passport_expiry_date?->format('Y-m-d') ?? '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Document type</span><span class="value">{{ $pax->document_type ? str_replace('_', ' ', $pax->document_type) : '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Passport issuing country</span><span class="value">{{ $pax->passport_issuing_country ? strtoupper($pax->passport_issuing_country) : '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Passport issued</span><span class="value">{{ $pax->passport_issue_date?->format('Y-m-d') ?? '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">National ID</span><span class="value">{{ $pax->national_id_number ?: '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Country of residence</span><span class="value">{{ $pax->country_of_residence ?: '—' }}</span></div>
                                <div class="passenger-kv"><span class="label">Place of birth</span><span class="value">{{ $pax->place_of_birth ?: '—' }}</span></div>
                                @if ($booking->tickets->isNotEmpty())
                                    @php
                                        $paxTickets = $booking->tickets->where('passenger_id', $pax->id);
                                    @endphp
                                    <div class="passenger-kv"><span class="label">Passenger-ticket mapping</span><span class="value">{{ $paxTickets->isNotEmpty() ? $paxTickets->pluck('ticket_number')->filter()->implode(', ') : 'No ticket mapped' }}</span></div>
                                @endif
                                @if($pax->is_lead_passenger && $booking->contact)
                                    <div class="passenger-kv"><span class="label">Lead contact</span><span class="value">{{ $booking->contact->phone ?? '—' }} / {{ $booking->contact->email ?? '—' }}</span></div>
                                @endif
                            </div>
                        </div>
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

            <div class="card mb-3" data-tab-section="payments">
                <div class="card-header"><h3 class="card-title mb-0">Fare</h3></div>
                <div class="card-body">
                    @if($booking->fareBreakdown)
                        @php
                            $f = $booking->fareBreakdown;
                            $metaPricing = is_array($booking->meta['pricing_snapshot'] ?? null) ? $booking->meta['pricing_snapshot'] : [];
                            $metaPassengerPricing = is_array($booking->meta['passenger_pricing'] ?? null) ? $booking->meta['passenger_pricing'] : [];
                            $supplierTotal = (float) ($booking->meta['supplier_total'] ?? 0);
                            $fxRate = $metaPricing['fx_rate'] ?? null;
                            $holdStatusLabel = (string) ($booking->supplier_hold_status ?? ($booking->meta['supplier_hold_status'] ?? 'not_started'));
                            $priceGuaranteeExpiry = (string) ($booking->price_guarantee_expires_at ?? ($booking->meta['price_guarantee_expires_at'] ?? ''));
                        @endphp
                        <div class="d-flex justify-content-between"><span>Supplier total</span><span>Rs {{ number_format($supplierTotal, 0) }}</span></div>
                        <div class="d-flex justify-content-between"><span>FX rate</span><span>{{ $fxRate !== null ? number_format((float) $fxRate, 4) : '—' }}</span></div>
                        <div class="d-flex justify-content-between"><span>Base</span><span>Rs {{ number_format((float) $f->base_fare, 0) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Taxes</span><span>Rs {{ number_format((float) $f->taxes, 0) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Markup/service fee</span><span>Rs {{ number_format(((float) $f->markup + (float) $f->fees), 0) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Hold status</span><span class="text-capitalize">{{ str_replace('_', ' ', $holdStatusLabel) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Price guarantee expiry</span><span>{{ $priceGuaranteeExpiry !== '' ? \Illuminate\Support\Carbon::parse($priceGuaranteeExpiry)->format('Y-m-d H:i') : '—' }}</span></div>
                        <div class="d-flex justify-content-between fw-bold mt-2 pt-2 border-top"><span>Total customer price</span><span>Rs {{ number_format((float) $f->total, 0) }}</span></div>
                        <div class="mt-2 pt-2 border-top">
                            <div class="small text-secondary mb-1">Passenger fare breakdown</div>
                            @if (! empty($metaPassengerPricing))
                                @foreach ($metaPassengerPricing as $idx => $pp)
                                    <div class="small d-flex justify-content-between">
                                        <span>Passenger {{ $idx + 1 }} {{ !empty($pp['passenger_type']) ? '('.strtoupper((string) $pp['passenger_type']).')' : '' }}</span>
                                        <span>Rs {{ number_format((float) ($pp['total_amount'] ?? 0), 0) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="small text-secondary">Passenger fare breakdown unavailable from supplier.</div>
                            @endif
                        </div>
                    @else
                        <p class="text-secondary mb-0">No fare breakdown.</p>
                    @endif
                </div>
            </div>
            <div class="card mb-3" data-tab-section="supplier">
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

            <div class="card mb-3" data-tab-section="communication" id="change-status-panel">
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

            <div class="card mb-3" data-tab-section="audit">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Audit / Timeline</h3>
                    @if ($p === 'admin')
                        <a href="{{ route('admin.bookings.audit.export', $booking) }}" class="btn btn-sm btn-outline-secondary">Export audit</a>
                    @endif
                </div>
                <div class="card-body">
                    @php
                        $timelineEvents = collect();
                        $timelineEvents->push([
                            'time' => $booking->created_at,
                            'type' => 'booking_created',
                            'title' => 'Booking created',
                            'actor' => 'System',
                            'status' => 'done',
                            'details' => 'Initial booking record created.',
                        ]);
                        if ($booking->passengers->isNotEmpty()) {
                            $timelineEvents->push([
                                'time' => $booking->passengers->max('created_at'),
                                'type' => 'passenger_details_submitted',
                                'title' => 'Passenger details submitted',
                                'actor' => 'Customer/Agent',
                                'status' => 'done',
                                'details' => 'Passenger manifest captured ('.$booking->passengers->count().' pax).',
                            ]);
                        }
                        if (! empty($booking->meta['validated_at'])) {
                            $timelineEvents->push([
                                'time' => \Illuminate\Support\Carbon::parse((string) $booking->meta['validated_at']),
                                'type' => 'offer_validated',
                                'title' => 'Offer validated',
                                'actor' => 'System',
                                'status' => (string) ($booking->meta['offer_validation_status'] ?? 'ok'),
                                'details' => 'Supplier offer validation completed.',
                            ]);
                        }
                        foreach ($booking->documents as $doc) {
                            $docType = (string) $doc->document_type->value;
                            $docTitle = match ($docType) {
                                'invoice' => 'Invoice generated',
                                default => 'Document generated',
                            };
                            $timelineEvents->push([
                                'time' => $doc->generated_at ?? $doc->created_at,
                                'type' => 'document_generated',
                                'title' => $docTitle,
                                'actor' => $doc->generatedBy?->name ?? 'System',
                                'status' => (string) $doc->status->value,
                                'details' => str_replace('_', ' ', $docType).' · '.($doc->document_number ?? 'No number'),
                            ]);
                        }
                        foreach ($booking->payments as $payment) {
                            $paymentStatus = (string) $payment->status->value;
                            $timelineEvents->push([
                                'time' => $payment->created_at,
                                'type' => 'payment_recorded',
                                'title' => 'Payment recorded',
                                'actor' => $payment->payer?->name ?? 'System',
                                'status' => $paymentStatus,
                                'details' => 'Amount '.$payment->amount.' '.strtoupper((string) ($payment->currency ?? 'PKR')),
                            ]);
                            if (in_array($paymentStatus, ['verified', 'rejected'], true)) {
                                $timelineEvents->push([
                                    'time' => $payment->updated_at,
                                    'type' => 'payment_reviewed',
                                    'title' => 'Payment '.($paymentStatus === 'verified' ? 'verified' : 'rejected'),
                                    'actor' => $payment->receiver?->name ?? 'Staff',
                                    'status' => $paymentStatus,
                                    'details' => 'Payment review completed.',
                                ]);
                            }
                        }
                        foreach ($booking->supplierBookings as $supplierBooking) {
                            $timelineEvents->push([
                                'time' => $supplierBooking->created_at,
                                'type' => 'supplier_booking_created',
                                'title' => 'Supplier booking created',
                                'actor' => $supplierBooking->createdBy?->name ?? 'System',
                                'status' => (string) $supplierBooking->status,
                                'details' => 'PNR: '.((string) ($supplierBooking->pnr ?? $booking->pnr ?: '—')),
                            ]);
                        }
                        foreach ($booking->tickets as $ticket) {
                            $timelineEvents->push([
                                'time' => $ticket->created_at,
                                'type' => 'ticket_issued',
                                'title' => 'Ticket issued',
                                'actor' => $ticket->issuedBy?->name ?? 'System',
                                'status' => (string) $ticket->status->value,
                                'details' => 'Ticket number: '.((string) ($ticket->ticket_number ?? '—')),
                            ]);
                        }
                        foreach ($booking->communicationLogs as $comm) {
                            $timelineEvents->push([
                                'time' => $comm->sent_at ?? $comm->created_at,
                                'type' => 'notification_sent',
                                'title' => 'Notification sent',
                                'actor' => $comm->user?->name ?? 'System',
                                'status' => (string) $comm->status,
                                'details' => str_replace('_', ' ', (string) $comm->event).' · '.strtoupper((string) $comm->channel),
                            ]);
                        }
                        foreach ($booking->statusLogs as $log) {
                            $timelineEvents->push([
                                'time' => $log->created_at,
                                'type' => 'status_changed',
                                'title' => 'Status changed',
                                'actor' => $log->user?->name ?? 'System',
                                'status' => 'done',
                                'details' => str_replace('_', ' ', (string) $log->from_status).' -> '.str_replace('_', ' ', (string) $log->to_status),
                            ]);
                        }
                        if ($booking->assigned_at) {
                            $timelineEvents->push([
                                'time' => $booking->assigned_at,
                                'type' => 'staff_assigned',
                                'title' => 'Staff assigned',
                                'actor' => 'System',
                                'status' => 'done',
                                'details' => 'Assigned to '.($booking->assignedStaff?->name ?? 'Unknown'),
                            ]);
                        }
                        foreach ($booking->bookingNotes as $note) {
                            $timelineEvents->push([
                                'time' => $note->created_at,
                                'type' => 'note_added',
                                'title' => 'Note added',
                                'actor' => $note->user?->name ?? 'System',
                                'status' => $note->is_customer_visible ? 'customer_visible' : 'internal',
                                'details' => \Illuminate\Support\Str::limit((string) $note->note, 140),
                            ]);
                        }
                        foreach ($booking->cancellationRequests as $cancellation) {
                            $timelineEvents->push([
                                'time' => $cancellation->created_at,
                                'type' => 'cancellation_event',
                                'title' => 'Cancellation event',
                                'actor' => $cancellation->requester?->name ?? 'System',
                                'status' => (string) $cancellation->status->value,
                                'details' => 'Cancellation workflow updated.',
                            ]);
                        }
                        foreach ($booking->refunds as $refund) {
                            $timelineEvents->push([
                                'time' => $refund->created_at,
                                'type' => 'refund_event',
                                'title' => 'Refund event',
                                'actor' => $refund->approver?->name ?? 'System',
                                'status' => (string) $refund->status->value,
                                'details' => 'Refund amount '.((string) $refund->amount).' '.strtoupper((string) ($refund->currency ?? 'PKR')),
                            ]);
                        }
                        $timelineEvents = $timelineEvents
                            ->filter(fn ($event) => $event['time'] !== null)
                            ->sortByDesc(fn ($event) => $event['time'])
                            ->values();
                    @endphp
                    <div class="small text-secondary mb-3">Append-only operational history. This timeline is read-only and does not allow log deletion.</div>
                    @forelse ($timelineEvents as $event)
                        <div class="timeline-entry">
                            <div class="small text-secondary">{{ $event['time']?->format('Y-m-d H:i') }} · {{ $event['actor'] }} · <span class="text-capitalize">{{ str_replace('_', ' ', $event['status']) }}</span></div>
                            <div class="fw-semibold">{{ $event['title'] }}</div>
                            <details class="small mt-1">
                                <summary>View details</summary>
                                <div class="mt-1 text-secondary">{{ $event['details'] }}</div>
                            </details>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">No timeline events logged.</p>
                    @endforelse
                </div>
            </div>

            <div class="card" data-tab-section="audit">
                <div class="card-header"><h3 class="card-title mb-0">Audit trail</h3></div>
                <div class="card-body">
                    @php
                        $staffAuditLogs = $auditLogs->filter(fn ($al) => $al->user !== null);
                        $systemAuditLogs = $auditLogs->filter(fn ($al) => $al->user === null);
                    @endphp
                    <h4 class="mb-2">Staff actions</h4>
                    @forelse ($staffAuditLogs as $al)
                        @php
                            $props = is_array($al->properties) ? $al->properties : [];
                            $newValues = is_array($props['new_values'] ?? null) ? $props['new_values'] : [];
                        @endphp
                        <div class="audit-row">
                            <div><span class="text-secondary">{{ $al->created_at?->format('Y-m-d H:i') }}</span> · <code>{{ $al->action }}</code> · {{ $al->user?->name }}</div>
                            @if (!empty($newValues))
                                <div class="small text-secondary mt-1">
                                    @foreach ($newValues as $k => $v)
                                        <div>{{ str_replace('_', ' ', (string) $k) }}: {{ is_scalar($v) || $v === null ? (string) ($v ?? '—') : '[complex value]' }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-secondary small mb-2">No staff actions logged.</p>
                    @endforelse

                    <h4 class="mb-2 mt-3">System events</h4>
                    @forelse ($systemAuditLogs as $al)
                        @php
                            $props = is_array($al->properties) ? $al->properties : [];
                            $newValues = is_array($props['new_values'] ?? null) ? $props['new_values'] : [];
                        @endphp
                        <div class="audit-row">
                            <div><span class="text-secondary">{{ $al->created_at?->format('Y-m-d H:i') }}</span> · <code>{{ $al->action }}</code> · System</div>
                            @if (!empty($newValues))
                                <div class="small text-secondary mt-1">
                                    @foreach ($newValues as $k => $v)
                                        <div>{{ str_replace('_', ' ', (string) $k) }}: {{ is_scalar($v) || $v === null ? (string) ($v ?? '—') : '[complex value]' }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">No system events logged.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3" data-tab-section="supplier">
                <div class="card-header"><h3 class="card-title mb-0">Supplier booking / PNR</h3></div>
                <div class="card-body">
                    @php
                        $meta = $booking->meta ?? [];
                        $provider = $meta['supplier_provider'] ?? ($booking->supplier ?? '—');
                        $validationStatus = $meta['offer_validation_status'] ?? 'unknown';
                        $latestAttempt = $booking->supplierBookingAttempts->sortByDesc('created_at')->first();
                        $hasSuccess = $booking->supplierBookings->contains(fn ($item) => in_array($item->status, ['created', 'pending_ticketing', 'ticketed'], true));
                        $bookingRoute = $p === 'staff' ? route('staff.bookings.supplier-booking', $booking) : route('admin.bookings.supplier-booking', $booking);
                        $manualPnrRoute = $p === 'staff' ? route('staff.bookings.manual-pnr', $booking) : route('admin.bookings.manual-pnr', $booking);
                        $providerSupportsPnr = in_array((string) $provider, ['duffel', 'sabre', 'pia', 'airline_direct', 'amadeus', 'travelport'], true);
                        $warnings = is_array($meta['validation_warnings'] ?? null) ? $meta['validation_warnings'] : [];
                        $safeSummary = is_array($latestAttempt?->safe_summary ?? null) ? $latestAttempt->safe_summary : [];
                        $lastValidatedAt = $meta['validated_at'] ?? null;
                        $lastValidationAtLabel = $lastValidatedAt ? \Illuminate\Support\Carbon::parse((string) $lastValidatedAt)->format('Y-m-d H:i') : '—';
                        $viewer = auth()->user();
                        $canViewDiagnostics = $viewer && method_exists($viewer, 'isPlatformAdmin') && ($viewer->isPlatformAdmin() || $viewer->isAgencyAdmin());
                        $canMarkManualPnr = $viewer && method_exists($viewer, 'isStaff') && ($viewer->isStaff() || $viewer->isAgencyAdmin() || $viewer->isPlatformAdmin());
                        $canValidateOffer = in_array((string) $validationStatus, ['ok', 'valid', 'fresh'], true);
                        $validateOfferReason = $canValidateOffer ? '' : 'Supplier unavailable';
                        $isPaid = (string) ($booking->payment_status ?? 'unpaid') === 'paid';
                        $passengerReadinessOkay = ($supplierBookingEligible ?? false);
                        $offerValid = in_array((string) $validationStatus, ['ok', 'valid', 'fresh'], true);
                        $canCreatePnr = $providerSupportsPnr && $isPaid && $passengerReadinessOkay && $offerValid && ! $hasSuccess;
                        $createPnrReason = ! $isPaid
                            ? 'Payment unpaid / invalid passengers / expired offer'
                            : (! $passengerReadinessOkay
                                ? 'Payment unpaid / invalid passengers / expired offer'
                                : (! $offerValid
                                    ? 'Payment unpaid / invalid passengers / expired offer'
                                    : ($hasSuccess ? 'Supplier booking already exists.' : ($providerSupportsPnr ? '' : 'Supplier unavailable'))));
                        $latestAttemptStatus = strtolower((string) ($latestAttempt->status ?? ''));
                        $canRetrySupplier = in_array($latestAttemptStatus, ['failed', 'manual_review'], true);
                        $retryReason = $canRetrySupplier ? '' : 'No failed attempt';
                    @endphp
                    <h4 class="mb-2">Supplier snapshot</h4>
                    <p class="mb-1"><strong>Provider:</strong> {{ $provider }}</p>
                    <p class="mb-1 text-capitalize"><strong>Offer validation status:</strong> {{ str_replace('_', ' ', (string) $validationStatus) }}</p>
                    <p class="mb-1 text-capitalize"><strong>Supplier booking status:</strong> {{ str_replace('_', ' ', (string) ($booking->supplier_booking_status ?? 'not started')) }}</p>
                    <p class="mb-1"><strong>Supplier reference:</strong> {{ $booking->supplier_reference ?? '—' }}</p>
                    <p class="mb-1"><strong>PNR:</strong> {{ $booking->pnr ?? '—' }}</p>
                    <p class="mb-2"><strong>Last validation time:</strong> {{ $lastValidationAtLabel }}</p>

                    <h4 class="mb-2">Last supplier attempt</h4>
                    @if ($latestAttempt)
                        <p class="mb-1 text-capitalize"><strong>Latest attempt:</strong> {{ $latestAttempt->status }}</p>
                        <p class="mb-1"><strong>Attempted at:</strong> {{ $latestAttempt->attempted_at?->format('Y-m-d H:i') ?? '—' }}</p>
                        <p class="mb-1"><strong>Completed at:</strong> {{ $latestAttempt->completed_at?->format('Y-m-d H:i') ?? '—' }}</p>
                        @if ($latestAttempt->error_message)
                            <div class="alert alert-warning py-2 px-3 small">{{ $latestAttempt->error_message }}</div>
                        @endif
                        @if (!empty($safeSummary))
                            <div class="small text-secondary mb-1"><strong>Safe error summary</strong></div>
                            <ul class="small mb-2">
                                @foreach ($safeSummary as $k => $v)
                                    <li>{{ str_replace('_', ' ', (string) $k) }}: {{ is_scalar($v) || $v === null ? (string) ($v ?? '—') : '[redacted]' }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @else
                        <p class="text-secondary small mb-2">No supplier attempt logged yet.</p>
                    @endif
                    @if (!empty($warnings))
                        <div class="small text-secondary mb-1"><strong>Errors / warnings</strong></div>
                        <ul class="small mb-2">
                            @foreach ($warnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <h4 class="mb-2">Actions</h4>
                    @if ($canCreatePnr)
                        <form method="post" action="{{ $bookingRoute }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 mb-2">Create supplier booking / PNR</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-outline-secondary w-100 mb-2" disabled>Create supplier booking / PNR</button>
                        <p class="text-muted small mt-n1 mb-2">{{ $createPnrReason !== '' ? $createPnrReason : 'Payment unpaid / invalid passengers / expired offer' }}</p>
                    @endif

                    <button type="button" class="btn btn-outline-primary w-100 mb-2" @disabled(! $canValidateOffer)>Validate offer</button>
                    @if (! $canValidateOffer)
                        <p class="text-muted small mt-n1 mb-2">{{ $validateOfferReason }}</p>
                    @else
                        <p class="text-muted small mt-n1 mb-2">Offer is currently valid on active supplier connection.</p>
                    @endif

                    @if ($canRetrySupplier)
                        <form method="post" action="{{ $bookingRoute }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100 mb-2">Retry supplier booking</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-outline-secondary w-100 mb-2" disabled>Retry supplier booking</button>
                        <p class="text-muted small mt-n1 mb-2">{{ $retryReason }}</p>
                    @endif

                    @if ($canMarkManualPnr)
                        <form method="post" action="{{ $manualPnrRoute }}" class="border rounded p-2 mb-2">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Manual PNR</label>
                                <input type="text" name="pnr" class="form-control" maxlength="32" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Supplier reference</label>
                                <input type="text" name="supplier_reference" class="form-control" maxlength="255">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Note</label>
                                <textarea name="note" class="form-control" rows="2" maxlength="1000"></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">Mark manual PNR</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-outline-secondary w-100" disabled>Mark manual PNR</button>
                        <p class="text-muted small mt-2 mb-2">Permission denied</p>
                    @endif

                    @if ($canViewDiagnostics)
                        <details class="mt-2">
                            <summary class="small fw-semibold">View safe diagnostics</summary>
                            <div class="small text-secondary mt-2">
                                <div><strong>Attempt ID:</strong> {{ $latestAttempt->id ?? '—' }}</div>
                                <div><strong>Error code:</strong> {{ $latestAttempt->error_code ?? '—' }}</div>
                                <div><strong>Error message:</strong> {{ $latestAttempt->error_message ?? '—' }}</div>
                                <div><strong>Safe summary fields:</strong> {{ !empty($safeSummary) ? implode(', ', array_keys($safeSummary)) : 'none' }}</div>
                            </div>
                        </details>
                    @else
                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" disabled>View safe diagnostics</button>
                        <p class="text-muted small mt-1 mb-0">Restricted</p>
                    @endif
                </div>
            </div>
            <div class="card mb-3" data-tab-section="ticketing">
                <div class="card-header"><h3 class="card-title mb-0">Ticketing</h3></div>
                <div class="card-body">
                    @php
                        $latestTicketAttempt = $booking->ticketingAttempts->sortByDesc('created_at')->first();
                        $ticketRoute = $p === 'staff' ? route('staff.bookings.issue-ticket', $booking) : route('admin.bookings.issue-ticket', $booking);
                        $provider = (string) ($booking->latestSupplierBooking?->provider ?? $booking->supplier ?? '');
                        $providerSupported = in_array($provider, ['sabre', 'pia', 'airline_direct'], true);
                        $isPaid = (string) ($booking->payment_status ?? 'unpaid') === 'paid';
                        $hasPnr = ((string) ($booking->pnr ?? '')) !== '';
                        $alreadyTicketed = $booking->tickets->isNotEmpty() || in_array((string) ($booking->ticketing_status ?? ''), ['ticketed', 'issued'], true);
                        $canIssueByRules = $isPaid && $hasPnr && ! $alreadyTicketed;
                        $canIssueTicket = $providerSupported && ($ticketingEligible ?? false) && $canIssueByRules;
                        $hasFailedTicketingAttempt = in_array((string) ($latestTicketAttempt?->status ?? ''), ['failed'], true);
                        $canRetryTicketing = $hasFailedTicketingAttempt;
                        $hasTicketArtifacts = $booking->tickets->isNotEmpty() || $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'ticket_itinerary');
                        $contactEmail = trim((string) ($booking->contact?->email ?? $booking->customer?->email ?? ''));
                        $contactPhone = trim((string) ($booking->contact?->phone ?? $booking->customer?->phone ?? ''));
                        $hasContact = $contactEmail !== '' || $contactPhone !== '';
                        $canSendTicketEmail = $hasTicketArtifacts && $hasContact;
                        $canVoidTicket = false;
                        $ticketRuleReason = ! $isPaid
                            ? 'Cannot issue ticket: payment is unpaid.'
                            : (! $hasPnr
                                ? 'Cannot issue ticket: supplier PNR is missing.'
                                : ($alreadyTicketed
                                    ? 'Cannot issue ticket twice: ticket already issued.'
                                    : (! $providerSupported
                                        ? 'Real supplier ticketing is not supported until certified.'
                                        : 'Ticketing prerequisites are not complete.')));
                    @endphp
                    <h4 class="mb-2">Ticketing eligibility checklist</h4>
                    <p class="mb-1"><strong>Provider:</strong> {{ $provider !== '' ? $provider : '—' }}</p>
                    <p class="mb-1 text-capitalize"><strong>Payment status:</strong> {{ str_replace('_', ' ', (string) ($booking->payment_status ?? 'unpaid')) }}</p>
                    <p class="mb-1"><strong>PNR:</strong> {{ $booking->pnr ?? '—' }}</p>
                    <p class="mb-1 text-capitalize"><strong>Supplier booking status:</strong> {{ str_replace('_', ' ', (string) ($booking->supplier_booking_status ?? 'not started')) }}</p>
                    <p class="mb-2 text-capitalize"><strong>Ticketing status:</strong> {{ str_replace('_', ' ', (string) ($booking->ticketing_status ?? 'not started')) }}</p>
                    <ul class="small mb-3">
                        <li class="{{ $isPaid ? 'text-success' : 'text-danger' }}">Payment is {{ $isPaid ? 'verified (paid)' : 'not paid' }}</li>
                        <li class="{{ $hasPnr ? 'text-success' : 'text-danger' }}">Supplier PNR is {{ $hasPnr ? 'available' : 'missing' }}</li>
                        <li class="{{ ! $alreadyTicketed ? 'text-success' : 'text-danger' }}">{{ $alreadyTicketed ? 'Ticket already issued' : 'No prior issued ticket' }}</li>
                        <li class="{{ $providerSupported ? 'text-success' : 'text-warning' }}">{{ $providerSupported ? 'Automated ticketing is supported for this provider integration.' : 'Automated ticketing is not available for this provider until the integration is certified.' }}</li>
                        <li class="{{ $booking->tickets->count() >= $booking->passengers->count() && $booking->passengers->count() > 0 ? 'text-success' : 'text-warning' }}">
                            Passenger-ticket mapping {{ $booking->tickets->count() >= $booking->passengers->count() && $booking->passengers->count() > 0 ? 'complete' : 'incomplete' }}
                        </li>
                    </ul>

                    <h4 class="mb-2">Actions</h4>
                    @if ($canIssueTicket)
                        <form method="post" action="{{ $ticketRoute }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 mb-2">Issue ticket</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-outline-secondary w-100 mb-2" disabled>Issue ticket</button>
                        <p class="text-muted small mt-n1 mb-2">Payment unpaid / PNR missing / already ticketed</p>
                    @endif

                    <form method="post" action="{{ $docItineraryUrl }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-100" @disabled($booking->tickets->isEmpty())>Generate ticket itinerary</button>
                    </form>
                    @if ($canRetryTicketing)
                        <form method="post" action="{{ $ticketRoute }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">Retry ticketing</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-outline-secondary w-100 mb-2" disabled>Retry ticketing</button>
                        <p class="text-muted small mt-n1 mb-2">No failed attempt</p>
                    @endif

                    @if ($booking->tickets->isEmpty())
                        <p class="text-muted small mt-n1 mb-2">No issued tickets</p>
                    @endif

                    <button type="button" class="btn btn-outline-primary w-100 mb-2" @disabled(! $canSendTicketEmail)>Send ticket email</button>
                    @if (! $canSendTicketEmail)
                        <p class="text-muted small mt-n1 mb-2">No ticket/contact</p>
                    @endif

                    <button type="button" class="btn btn-outline-secondary w-100 mb-3" @disabled(! $canVoidTicket)>Void ticket / request void</button>
                    @if (! $canVoidTicket)
                        <p class="text-muted small mt-n1 mb-3">No ticket / void not allowed</p>
                    @endif

                    <h4 class="mb-2">Latest ticketing attempt</h4>
                    @if ($latestTicketAttempt)
                        <div class="mt-3 small">
                            <strong>Latest attempt:</strong> <span class="text-capitalize">{{ $latestTicketAttempt->status }}</span>
                            <div><strong>Attempted at:</strong> {{ $latestTicketAttempt->attempted_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            <div><strong>Completed at:</strong> {{ $latestTicketAttempt->completed_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            @if ($latestTicketAttempt->error_message)
                                <div class="alert alert-warning py-2 px-3 mt-2 mb-0">
                                    <strong>Ticketing error:</strong> {{ $latestTicketAttempt->error_message }}
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="small text-secondary mb-3">No ticketing attempt logged yet.</p>
                    @endif

                    <h4 class="mb-2">Issued tickets</h4>
                    @if ($booking->tickets->isNotEmpty())
                        @foreach ($booking->tickets as $ticket)
                            <div class="border rounded p-2 mb-2">
                                <div><strong>Ticket number:</strong> {{ $ticket->ticket_number ?? '—' }}</div>
                                <div><strong>PNR:</strong> {{ $ticket->pnr ?? '—' }}</div>
                                <div><strong>Ticket issue date:</strong> {{ $ticket->issued_at?->format('Y-m-d H:i') ?? '—' }}</div>
                                <div class="small text-secondary"><strong>Passenger mapping:</strong> {{ $ticket->passenger?->first_name }} {{ $ticket->passenger?->last_name }} (ID: {{ $ticket->passenger_id ?? '—' }})</div>
                                <div class="small text-secondary"><strong>Provider type:</strong> {{ $providerSupported ? 'Mock ticketing' : 'Real provider (not supported)' }}</div>
                            </div>
                        @endforeach
                    @else
                        <p class="small text-secondary mb-0">No issued tickets yet.</p>
                    @endif
                </div>
            </div>
            <div class="card mb-3" id="payments" data-tab-section="payments">
                <div class="card-header"><h3 class="card-title mb-0">Payments</h3></div>
                <div class="card-body">
                    @php
                        $verifiedTotal = (float) ($booking->amount_paid ?? 0);
                        $balanceDue = $booking->balance_due !== null ? (float) $booking->balance_due : max(0, $totalDue - $verifiedTotal);
                        $contactEmail = trim((string) ($booking->contact?->email ?? $booking->customer?->email ?? ''));
                        $contactPhone = trim((string) ($booking->contact?->phone ?? $booking->customer?->phone ?? ''));
                        $hasContact = $contactEmail !== '' || $contactPhone !== '';
                        $isPaymentBlockedByBookingState = in_array((string) $booking->status->value, ['cancelled', 'refunded'], true);
                        $hasPendingProof = $booking->payments->contains(fn ($pay) => (string) $pay->status->value === 'submitted' && (string) ($pay->proof_path ?? '') !== '');
                        $hasVerifiedPayment = $booking->payments->contains(fn ($pay) => (string) $pay->status->value === 'verified');
                        $viewer = auth()->user();
                        $canUseMarkPaidOverride = $viewer && (method_exists($viewer, 'isAgencyAdmin') && ($viewer->isAgencyAdmin() || $viewer->isPlatformAdmin()));
                        $canRecordManualPayment = $balanceDue > 0 && ! $isPaymentBlockedByBookingState;
                        $recordManualPaymentReason = $canRecordManualPayment ? '' : ($isPaymentBlockedByBookingState ? 'No balance / cancelled' : 'No balance / cancelled');
                        $canMarkAsPaid = (($verifiedTotal >= $totalDue && $totalDue > 0) || ($canUseMarkPaidOverride && $balanceDue > 0 && ! $isPaymentBlockedByBookingState));
                        $markAsPaidReason = $canMarkAsPaid ? '' : 'Insufficient paid amount';
                        $canGeneratePaymentReceipt = $hasVerifiedPayment;
                        $receiptReason = $canGeneratePaymentReceipt ? '' : 'No verified payment';
                        $canSendReminder = $balanceDue > 0 && $hasContact;
                        $sendReminderReason = $canSendReminder ? '' : 'No balance / no contact';
                        $canSendConfirmation = in_array((string) ($booking->payment_status ?? 'unpaid'), ['paid', 'partial'], true) && $hasContact;
                        $sendConfirmationReason = $canSendConfirmation ? '' : 'No verified payment';
                        $verificationHistory = $booking->payments
                            ->filter(fn ($pay) => in_array((string) $pay->status->value, ['verified', 'rejected'], true))
                            ->sortByDesc('updated_at')
                            ->values();
                        $paymentReceiptDocs = $booking->documents
                            ->filter(fn ($doc) => (string) $doc->document_type->value === 'payment_receipt')
                            ->sortByDesc('created_at')
                            ->values();
                    @endphp
                    <h4 class="mb-2">Payment summary</h4>
                    <p class="mb-1"><strong>Total amount:</strong> Rs {{ number_format($totalDue, 0) }}</p>
                    <p class="mb-1"><strong>Paid amount:</strong> Rs {{ number_format($verifiedTotal, 0) }}</p>
                    <p class="mb-1"><strong>Balance:</strong> Rs {{ number_format($balanceDue, 0) }}</p>
                    <p class="mb-3 text-capitalize"><strong>Payment status:</strong> {{ str_replace('_', ' ', (string) ($booking->payment_status ?? 'unpaid')) }}</p>

                    <h4 class="mb-2">Actions</h4>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @if ($hasPendingProof)
                            <button type="button" class="btn btn-sm btn-primary">Verify payment proof</button>
                        @else
                            <a href="#payment-record-form" class="btn btn-sm btn-primary {{ $canRecordManualPayment ? '' : 'disabled' }}" @if(! $canRecordManualPayment) aria-disabled="true" @endif>Record manual payment</a>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-danger" @disabled(! $hasPendingProof)>Reject payment proof</button>
                        @if ($canUseMarkPaidOverride && $balanceDue > 0 && ! $isPaymentBlockedByBookingState)
                            <form method="post" action="{{ $paymentStoreUrl }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="method" value="other">
                                <input type="hidden" name="amount" value="{{ max(1, $balanceDue) }}">
                                <input type="hidden" name="payment_reference" value="MARK-AS-PAID">
                                <input type="hidden" name="notes" value="Marked as paid from payments tab (admin override)">
                                <input type="hidden" name="admin_override" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Mark as paid</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-primary" @disabled(! $canMarkAsPaid)>Mark as paid</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-primary" @disabled(! $canGeneratePaymentReceipt)>Generate payment receipt</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" @disabled(! $canSendReminder)>Send payment reminder</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" @disabled(! $canSendConfirmation)>Send payment confirmation</button>
                    </div>
                    @if (! $canRecordManualPayment)
                        <div class="small text-secondary mb-1"><strong>Record manual payment:</strong> {{ $recordManualPaymentReason }}</div>
                    @endif
                    @if (! $hasPendingProof)
                        <div class="small text-secondary mb-1"><strong>Verify/reject payment proof:</strong> No pending proof</div>
                    @endif
                    @if (! $canMarkAsPaid)
                        <div class="small text-secondary mb-1"><strong>Mark as paid:</strong> {{ $markAsPaidReason }}</div>
                    @endif
                    @if (! $canGeneratePaymentReceipt)
                        <div class="small text-secondary mb-1"><strong>Generate receipt:</strong> {{ $receiptReason }}</div>
                    @endif
                    @if (! $canSendReminder)
                        <div class="small text-secondary mb-1"><strong>Send reminder:</strong> {{ $sendReminderReason }}</div>
                    @endif
                    @if (! $canSendConfirmation)
                        <div class="small text-secondary mb-3"><strong>Send confirmation:</strong> {{ $sendConfirmationReason }}</div>
                    @endif
                    <div class="small text-secondary mb-3">Payment proof alone does not mean paid. Only verified payments increase paid amount and unlock ticketing.</div>

                    <h4 class="mb-2" id="payment-record-form">Manual payment form</h4>
                    <div class="small text-secondary mb-2">Manual/offline payment records only. No gateway connected.</div>
                    <form method="post" action="{{ $paymentStoreUrl }}" class="mb-3" enctype="multipart/form-data">
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
                        <div class="mb-2">
                            <label class="form-label">Payment proof upload</label>
                            <input name="payment_proof" type="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.webp">
                            <div class="small text-secondary mt-1">Accepted: JPG, PNG, WEBP, PDF (max 5MB).</div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $canRecordManualPayment)>Record manual payment</button>
                    </form>
                    @if ($errors->has('payment'))
                        <div class="alert alert-warning py-2 px-3 small">{{ $errors->first('payment') }}</div>
                    @endif
                    <h4 class="mb-2">Payment records</h4>
                    @foreach ($booking->payments->sortByDesc('created_at') as $payment)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>Rs {{ number_format((float) $payment->amount, 0) }}</strong>
                                <span class="text-capitalize">{{ str_replace('_', ' ', $payment->status->value) }}</span>
                            </div>
                            <div class="small text-secondary">{{ str_replace('_', ' ', $payment->method->value) }} · {{ $payment->payment_reference ?? 'No ref' }}</div>
                            <div class="small text-secondary">Submitted: {{ $payment->submitted_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            <div class="small text-secondary">Verified: {{ $payment->verified_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            <div class="small text-secondary">Rejected: {{ $payment->rejected_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            @if($payment->proof_path)
                                <div class="small mt-1"><strong>Proof file:</strong> <code>{{ $payment->proof_path }}</code></div>
                            @endif
                            @if($payment->documents->isNotEmpty())
                                <div class="small mt-2"><strong>Payment proof uploads</strong></div>
                                @foreach ($payment->documents as $proofDoc)
                                    <div class="small text-secondary">- {{ str_replace('_', ' ', $proofDoc->document_type->value) }} @if($proofDoc->generated_at) · {{ $proofDoc->generated_at->format('Y-m-d H:i') }} @endif</div>
                                @endforeach
                            @endif
                            @if ($payment->status->value === 'submitted')
                                @php
                                    $verifyUrl = $p === 'staff' ? route('staff.bookings.payments.verify', $payment) : route('admin.bookings.payments.verify', $payment);
                                    $rejectUrl = $p === 'staff' ? route('staff.bookings.payments.reject', $payment) : route('admin.bookings.payments.reject', $payment);
                                @endphp
                                <div class="small text-secondary mt-2 mb-1"><strong>Verify/reject actions</strong></div>
                                <form method="post" action="{{ $verifyUrl }}" class="mt-2">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success">Verify payment</button>
                                </form>
                                <form method="post" action="{{ $rejectUrl }}" class="mt-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="reason" value="Rejected during admin review">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject payment</button>
                                </form>
                            @endif
                            @if ($payment->status->value === 'verified')
                                <div class="small text-secondary mt-2 mb-1"><strong>Generate payment receipt</strong></div>
                                <form method="post" action="{{ route($docReceiptRoute, $payment) }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Generate payment receipt</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" @disabled(! $canSendConfirmation)>Send payment confirmation</button>
                            @endif
                        </div>
                    @endforeach

                    <h4 class="mb-2 mt-3">Payment verification history</h4>
                    @forelse ($verificationHistory->take(10) as $item)
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between">
                                <strong>Payment #{{ $item->id }}</strong>
                                <span class="text-capitalize">{{ str_replace('_', ' ', $item->status->value) }}</span>
                            </div>
                            <div class="text-secondary">Amount: Rs {{ number_format((float) $item->amount, 0) }}</div>
                            <div class="text-secondary">Verified at: {{ $item->verified_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            <div class="text-secondary">Rejected at: {{ $item->rejected_at?->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                    @empty
                        <p class="small text-secondary mb-2">No payment verification history yet.</p>
                    @endforelse

                    <h4 class="mb-2 mt-3">Receipts</h4>
                    @forelse ($paymentReceiptDocs->take(10) as $receipt)
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $receipt->document_number ?? 'Payment receipt' }}</strong>
                                <span class="text-capitalize">{{ str_replace('_', ' ', $receipt->status->value) }}</span>
                            </div>
                            <div class="text-secondary">{{ $receipt->generated_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            @if ($receipt->file_path)
                                <a class="btn btn-sm btn-outline-secondary mt-1" href="{{ route($docDownloadRoute, $receipt) }}">Download receipt</a>
                            @endif
                        </div>
                    @empty
                        <p class="small text-secondary mb-0">No payment receipts generated yet.</p>
                    @endforelse
                </div>
            </div>
            <div class="card mb-3" data-tab-section="refunds">
                <div class="card-header"><h3 class="card-title mb-0">Cancellation &amp; Refund</h3></div>
                <div class="card-body">
                    @php
                        $verifiedPaidAmount = (float) ($booking->payments->where('status.value', 'verified')->sum('amount') ?? 0);
                        $paidRefundAmount = (float) ($booking->refunds->where('status.value', 'paid')->sum('amount') ?? 0);
                        $refundableAmount = max(0, $verifiedPaidAmount - $paidRefundAmount);
                        $ticketingStatusLabel = str_replace('_', ' ', (string) ($booking->ticketing_status ?? 'not_started'));
                        $latestCancellation = $booking->cancellationRequests->sortByDesc('created_at')->first();
                        $latestPendingCancellation = $booking->cancellationRequests->first(fn ($c) => (string) $c->status->value === 'requested');
                        $latestApprovedCancellation = $booking->cancellationRequests->first(fn ($c) => (string) $c->status->value === 'approved');
                        $latestPendingRefund = $booking->refunds->first(fn ($r) => (string) $r->status->value === 'pending');
                        $latestApprovedRefund = $booking->refunds->first(fn ($r) => (string) $r->status->value === 'approved');
                        $eligibleRefundForNote = $booking->refunds->first(fn ($r) => in_array((string) $r->status->value, ['approved', 'paid'], true));
                        $canRequestCancellation = ! in_array((string) $booking->status->value, ['cancelled'], true);
                        $canApproveCancellation = $latestPendingCancellation !== null;
                        $canRejectCancellation = $latestPendingCancellation !== null;
                        $canProcessCancellation = $latestApprovedCancellation !== null;
                        $canCreateRefund = $refundableAmount > 0;
                        $canApproveRefund = $latestPendingRefund !== null;
                        $canMarkRefundPaid = $latestApprovedRefund !== null;
                        $canGenerateRefundNoteTab = $eligibleRefundForNote !== null;
                        $hasRefundContact = (trim((string) ($booking->contact?->email ?? '')) !== '') || (trim((string) ($booking->contact?->phone ?? '')) !== '');
                        $canSendRefundUpdate = $hasRefundContact && $booking->refunds->isNotEmpty();
                        $docRefundNoteUrlTab = $p === 'staff' ? route('staff.bookings.documents.refund-note', $booking) : route('admin.bookings.documents.refund-note', $booking);
                    @endphp
                    <h4 class="mb-2">Refund status</h4>
                    <p class="mb-1"><strong>Booking status:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', $booking->status->value) }}</span></p>
                    <p class="mb-1"><strong>Cancellation status:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', (string) ($booking->cancellation_status ?? 'none')) }}</span></p>
                    <p class="mb-1"><strong>Refund status:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', (string) ($booking->refund_status ?? 'none')) }}</span></p>
                    <p class="mb-1"><strong>Paid amount:</strong> Rs {{ number_format($verifiedPaidAmount, 0) }}</p>
                    <p class="mb-1"><strong>Refundable amount:</strong> Rs {{ number_format($refundableAmount, 0) }}</p>
                    <p class="mb-2"><strong>Ticketing status:</strong> <span class="text-capitalize">{{ $ticketingStatusLabel }}</span></p>
                    @if ($booking->status->value === 'ticketed')
                        <div class="alert alert-warning py-2 px-3 small">
                            Manual supplier warning: ticketed cancellation requires manual supplier/airline review before final refund processing.
                        </div>
                    @endif
                    <div class="alert alert-secondary py-2 px-3 small">
                        Refund records are manual only and do not trigger gateway/bank transfers.
                    </div>

                    <h4 class="mb-2">Actions</h4>
                    <div class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-primary w-100" @disabled(! $canProcessCancellation && ! $canCreateRefund)>{{ $canProcessCancellation ? 'Process cancellation' : 'Create refund' }}</button>
                        <button type="button" class="btn btn-outline-primary w-100" @disabled(! $canRequestCancellation)>Request cancellation</button>
                        <button type="button" class="btn btn-outline-primary w-100" @disabled(! $canApproveCancellation)>Approve cancellation</button>
                        <button type="button" class="btn btn-outline-primary w-100" @disabled(! $canCreateRefund)>Create refund</button>
                        <button type="button" class="btn btn-outline-primary w-100" @disabled(! $canApproveRefund)>Approve refund</button>
                        <button type="button" class="btn btn-outline-primary w-100" @disabled(! $canMarkRefundPaid)>Mark refund paid</button>
                        <button type="button" class="btn btn-outline-danger w-100" @disabled(! $canRejectCancellation)>Reject cancellation</button>
                        <button type="button" class="btn btn-outline-danger w-100" @disabled(! $canApproveRefund)>Reject refund</button>
                        <form method="post" action="{{ $docRefundNoteUrlTab }}">@csrf<button type="submit" class="btn btn-outline-primary w-100" @disabled(! $canGenerateRefundNoteTab)>Generate refund note</button></form>
                        <button type="button" class="btn btn-outline-secondary w-100" @disabled(! $canSendRefundUpdate)>Send refund update</button>
                    </div>
                    @if (! $canRequestCancellation)<div class="small text-secondary mb-1">Request cancellation: Already cancelled</div>@endif
                    @if (! $canApproveCancellation)<div class="small text-secondary mb-1">Approve cancellation: No pending request</div>@endif
                    @if (! $canRejectCancellation)<div class="small text-secondary mb-1">Reject cancellation: No pending request</div>@endif
                    @if (! $canProcessCancellation)<div class="small text-secondary mb-1">Process cancellation: Not approved</div>@endif
                    @if (! $canCreateRefund)<div class="small text-secondary mb-1">Create refund: No refundable paid amount</div>@endif
                    @if (! $canApproveRefund)<div class="small text-secondary mb-1">Approve refund: No pending refund</div>@endif
                    @if (! $canMarkRefundPaid)<div class="small text-secondary mb-1">Mark refund paid: Refund not approved</div>@endif
                    @if (! $canGenerateRefundNoteTab)<div class="small text-secondary mb-1">Generate refund note: No refund</div>@endif
                    @if (! $canSendRefundUpdate)<div class="small text-secondary mb-3">Send refund update: No refund/contact</div>@endif

                    <h4 class="mb-2 mt-3">Request cancellation</h4>
                    <form method="post" action="{{ $cancelStoreUrl }}" class="mb-3 border rounded p-2">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Cancellation type</label>
                            <select name="cancellation_type" class="form-select" required>
                                @foreach (['booking_cancel', 'ticket_void', 'ticket_refund', 'supplier_cancel'] as $type)
                                    <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea name="reason" class="form-control" rows="2" placeholder="Reason (optional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100" @disabled(! $canRequestCancellation)>Submit cancellation request</button>
                    </form>

                    <h4 class="mb-2">Cancellation requests</h4>
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
                                    <button type="submit" class="btn btn-sm btn-success" @disabled((string) $latestCancellation->status->value !== 'requested')>Approve cancellation</button>
                                </form>
                                <form method="post" action="{{ $processUrl }}" class="mt-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-primary" @disabled((string) $latestCancellation->status->value !== 'approved')>Process cancellation</button>
                                </form>
                                <form method="post" action="{{ $rejectUrl }}" class="mt-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="reason" value="Rejected by operations review">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" @disabled((string) $latestCancellation->status->value !== 'requested')>Reject cancellation</button>
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

                    <h4 class="mb-2">Manual refund form</h4>
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
                        <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $canCreateRefund)>Create refund</button>
                    </form>

                    <h4 class="mb-2">Refund records</h4>
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
                                <form method="post" action="{{ $refundApproveUrl }}" class="mt-1">@csrf @method('PATCH')<button type="submit" class="btn btn-sm btn-success" @disabled((string) $refund->status->value !== 'pending')>Approve refund</button></form>
                                <form method="post" action="{{ $refundPaidUrl }}" class="mt-1">@csrf @method('PATCH')<button type="submit" class="btn btn-sm btn-primary" @disabled((string) $refund->status->value !== 'approved')>Mark refund paid</button></form>
                                <form method="post" action="{{ $refundRejectUrl }}" class="mt-1">@csrf @method('PATCH')<input type="hidden" name="reason" value="Rejected by operations"><button type="submit" class="btn btn-sm btn-outline-danger" @disabled((string) $refund->status->value !== 'pending')>Reject refund</button></form>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" @disabled(! $canSendRefundUpdate)>Send refund update</button>
                        </div>
                    @empty
                        <div class="text-secondary small">No refunds yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="card mb-3" data-tab-section="documents">
                <div class="card-header"><h3 class="card-title mb-0">Documents</h3></div>
                <div class="card-body">
                    @php
                        $bookingExists = $booking->exists;
                        $verifiedPayment = $booking->payments->firstWhere('status.value', 'verified');
                        $hasFareSnapshot = $booking->fareBreakdown !== null;
                        $hasPassengerSnapshot = $booking->passengers->isNotEmpty();
                        $hasContactSnapshot = $booking->contact !== null && (
                            trim((string) ($booking->contact->email ?? '')) !== '' ||
                            trim((string) ($booking->contact->phone ?? '')) !== ''
                        );
                        $hasTotalAmount = (float) ($booking->fareBreakdown?->total ?? 0) > 0;
                        $approvedOrPaidRefund = $booking->refunds->first(fn ($r) => in_array((string) $r->status->value, ['approved', 'paid'], true));
                        $processedCancellation = $booking->cancellationRequests->first(fn ($c) => (string) $c->status->value === 'processed');
                        $canGenerateConfirmation = $bookingExists && $hasPassengerSnapshot && $hasContactSnapshot && $hasFareSnapshot;
                        $canGenerateInvoice = $hasFareSnapshot && $hasTotalAmount;
                        $canGenerateReceipt = $verifiedPayment !== null;
                        $canGenerateItinerary = $booking->tickets->isNotEmpty();
                        $canGenerateRefundNote = $approvedOrPaidRefund !== null;
                        $canGenerateCancellationConfirmation = $processedCancellation !== null;
                        $docRefundNoteUrl = $p === 'staff' ? route('staff.bookings.documents.refund-note', $booking) : route('admin.bookings.documents.refund-note', $booking);
                        $docCancellationConfirmationUrl = $p === 'staff' ? route('staff.bookings.documents.cancellation-confirmation', $booking) : route('admin.bookings.documents.cancellation-confirmation', $booking);
                        $contactExists = $hasContactSnapshot;
                        $hasConfirmationDoc = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'booking_confirmation');
                        $hasInvoiceDoc = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'invoice');
                        $hasReceiptDoc = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'payment_receipt');
                        $hasItineraryDoc = $booking->documents->contains(fn ($doc) => (string) $doc->document_type->value === 'ticket_itinerary');
                        $missingDocumentLabel = 'Generate booking confirmation';
                        $missingDocumentRoute = $docConfirmationUrl;
                        $canGenerateMissingDocument = $canGenerateConfirmation;
                        if (! $hasInvoiceDoc) {
                            $missingDocumentLabel = 'Generate invoice';
                            $missingDocumentRoute = $docInvoiceUrl;
                            $canGenerateMissingDocument = $canGenerateInvoice;
                        } elseif (! $hasConfirmationDoc) {
                            $missingDocumentLabel = 'Generate booking confirmation';
                            $missingDocumentRoute = $docConfirmationUrl;
                            $canGenerateMissingDocument = $canGenerateConfirmation;
                        } elseif (! $hasReceiptDoc) {
                            $missingDocumentLabel = 'Generate payment receipt';
                            $missingDocumentRoute = $verifiedPayment ? route($docReceiptRoute, $verifiedPayment) : null;
                            $canGenerateMissingDocument = $canGenerateReceipt;
                        } elseif (! $hasItineraryDoc) {
                            $missingDocumentLabel = 'Generate ticket itinerary';
                            $missingDocumentRoute = $docItineraryUrl;
                            $canGenerateMissingDocument = $canGenerateItinerary;
                        }
                    @endphp

                    <h4 class="mb-2">Actions</h4>
                    <div class="d-grid gap-2 mb-2">
                        @if ($missingDocumentRoute)
                            <form method="post" action="{{ $missingDocumentRoute }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100" @disabled(! $canGenerateMissingDocument)>{{ $missingDocumentLabel }}</button>
                            </form>
                        @endif
                        <form method="post" action="{{ $docConfirmationUrl }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $canGenerateConfirmation)>Generate booking confirmation</button>
                        </form>
                        @if (! $canGenerateConfirmation)
                            <div class="small text-secondary">Cannot generate booking confirmation: missing booking data.</div>
                        @endif

                        <form method="post" action="{{ $docInvoiceUrl }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $canGenerateInvoice)>Generate invoice</button>
                        </form>
                        @if (! $canGenerateInvoice)
                            <div class="small text-secondary">Cannot generate invoice: missing fare/total.</div>
                        @endif

                        @if ($canGenerateReceipt)
                            <form method="post" action="{{ route($docReceiptRoute, $verifiedPayment) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary w-100">Generate payment receipt</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-outline-secondary w-100" disabled>Generate payment receipt</button>
                            <div class="small text-secondary">Cannot generate payment receipt: payment unverified/unpaid.</div>
                        @endif

                        <form method="post" action="{{ $docItineraryUrl }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $canGenerateItinerary)>Generate ticket itinerary</button>
                        </form>
                        @if (! $canGenerateItinerary)
                            <div class="small text-secondary">Cannot generate ticket itinerary: no issued tickets.</div>
                        @endif

                        <form method="post" action="{{ $docRefundNoteUrl }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $canGenerateRefundNote)>Generate refund note</button>
                        </form>
                        @if (! $canGenerateRefundNote)
                            <div class="small text-secondary">Cannot generate refund note: no refund record.</div>
                        @endif

                        <form method="post" action="{{ $docCancellationConfirmationUrl }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $canGenerateCancellationConfirmation)>Generate cancellation confirmation</button>
                        </form>
                        @if (! $canGenerateCancellationConfirmation)
                            <div class="small text-secondary">Cannot generate cancellation confirmation: cancellation not processed.</div>
                        @endif
                    </div>
                    <div class="small text-secondary mb-1">Invoice lifecycle: generated → sent to customer/agent → payment proof submitted/manual payment recorded → payment verified → receipt generated.</div>
                    <div class="small text-secondary mb-3">Invoice is a request/record of payable amount, not proof of payment.</div>
                    <div class="small text-secondary mb-3">
                        <strong>Document types:</strong> booking confirmation, invoice, payment receipt, ticket itinerary, refund note, cancellation confirmation.
                    </div>

                    <h4 class="mb-2">Document records</h4>
                    @forelse ($booking->documents->sortByDesc('created_at') as $document)
                        @php
                            $docTypeRaw = (string) $document->document_type->value;
                            $docTypeLabel = DocumentOperationalState::typeLabel($docTypeRaw);
                            $docStatusMap = DocumentOperationalState::statusForDocument(
                                (string) $document->status->value,
                                (string) ($document->file_path ?? '') !== '',
                                in_array((string) ($document->meta['state'] ?? ''), ['voided', 'cancelled'], true)
                            );
                        @endphp
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $docTypeLabel }}</strong>
                                <span class="text-capitalize">{{ $docStatusMap['label'] }}</span>
                            </div>
                            <div class="small text-secondary">{{ $document->document_number ?? 'N/A' }} · {{ $document->generated_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @if ($docStatusMap['code'] === 'generated' && $document->file_path)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route($docDownloadRoute, $document) }}">Download</a>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Download</button>
                                @endif

                                @php
                                    $docType = $docTypeRaw;
                                    $regenRoute = match ($docType) {
                                        'booking_confirmation' => $docConfirmationUrl,
                                        'invoice' => $docInvoiceUrl,
                                        'ticket_itinerary' => $docItineraryUrl,
                                        'payment_receipt' => ($document->booking_payment_id && $document->bookingPayment) ? route($docReceiptRoute, $document->bookingPayment) : null,
                                        'refund_note' => $docRefundNoteUrl,
                                        'cancellation_confirmation' => $docCancellationConfirmationUrl,
                                        default => null,
                                    };
                                @endphp
                                @if ($regenRoute)
                                    <form method="post" action="{{ $regenRoute }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Regenerate</button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Regenerate</button>
                                @endif

                                <button type="button" class="btn btn-sm btn-outline-secondary" @disabled(! $contactExists)>Send to customer</button>
                            </div>
                            <div class="small text-secondary mt-1">
                                @if (! $contactExists)
                                    No contact available for document delivery.
                                @else
                                    Send to customer action is available through configured communication workflows.
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">No documents generated yet.</p>
                    @endforelse
                </div>
            </div>
            <div class="card mb-3" data-tab-section="communication" id="add-note-panel">
                <div class="card-header"><h3 class="card-title mb-0">Communication</h3></div>
                <div class="card-body">
                    @php
                        $commLogs = $booking->communicationLogs->sortByDesc('created_at');
                        $failedLogs = $commLogs->filter(fn ($c) => in_array((string) $c->status, ['failed', 'error'], true));
                        $customerLogs = $commLogs->filter(fn ($c) => !empty($c->recipient_email) || !empty($c->recipient_phone));
                        $adminLogs = $commLogs->filter(fn ($c) => empty($c->recipient_email) && empty($c->recipient_phone));
                    @endphp

                    <h4 class="mb-2">Actions</h4>
                    <div class="d-grid gap-2 mb-3">
                        <form method="post" action="{{ $communicationSendUrl }}">
                            @csrf
                            <input type="hidden" name="action" value="booking_update">
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $communicationSendUrl || ! $hasContact)>Send booking update</button>
                        </form>
                        @if (! $hasContact)
                            <div class="small text-secondary mt-n2">Requires booking contact (email or phone).</div>
                        @endif

                        <form method="post" action="{{ $communicationSendUrl }}">
                            @csrf
                            <input type="hidden" name="action" value="payment_reminder">
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $communicationSendUrl || ! $hasContact || ! $hasUnpaidOrPartialBalance)>Send payment reminder</button>
                        </form>
                        @if (! $hasUnpaidOrPartialBalance)
                            <div class="small text-secondary mt-n2">Requires unpaid or partial balance.</div>
                        @endif

                        <form method="post" action="{{ $communicationSendUrl }}">
                            @csrf
                            <input type="hidden" name="action" value="invoice">
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $communicationSendUrl || ! $hasContact || ! $hasInvoiceDocument)>Send invoice</button>
                        </form>
                        @if (! $hasInvoiceDocument)
                            <div class="small text-secondary mt-n2">Requires an existing invoice document.</div>
                        @endif

                        <form method="post" action="{{ $communicationSendUrl }}">
                            @csrf
                            <input type="hidden" name="action" value="receipt">
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $communicationSendUrl || ! $hasContact || ! $hasReceiptDocument)>Send receipt</button>
                        </form>
                        @if (! $hasReceiptDocument)
                            <div class="small text-secondary mt-n2">Requires an existing payment receipt document.</div>
                        @endif

                        <form method="post" action="{{ $communicationSendUrl }}">
                            @csrf
                            <input type="hidden" name="action" value="ticket_itinerary">
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $communicationSendUrl || ! $hasContact || ! $hasItineraryDocument)>Send ticket itinerary</button>
                        </form>
                        @if (! $hasItineraryDocument)
                            <div class="small text-secondary mt-n2">Requires issued itinerary/ticket.</div>
                        @endif

                        <form method="post" action="{{ $communicationSendUrl }}">
                            @csrf
                            <input type="hidden" name="action" value="cancellation_update">
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $communicationSendUrl || ! $hasContact || ! $hasCancellationDocument)>Send cancellation update</button>
                        </form>

                        <form method="post" action="{{ $communicationSendUrl }}">
                            @csrf
                            <input type="hidden" name="action" value="refund_update">
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $communicationSendUrl || ! $hasContact || ! $hasRefundDocument)>Send refund update</button>
                        </form>

                        <button type="button" class="btn btn-outline-secondary w-100" @disabled($failedLogs->isEmpty())>Resend failed notification</button>
                    </div>
                    <div class="small text-secondary mb-3">
                        Security filter is applied to outbound payloads and failed reasons; sensitive values are redacted.
                    </div>

                    <h4 class="mb-2">Communication logs</h4>
                    @forelse ($commLogs->take(12) as $comm)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ str_replace('_', ' ', $comm->event) }}</strong>
                                <span class="text-capitalize">{{ $comm->status }}</span>
                            </div>
                            <div class="small text-secondary">
                                Event type: <strong>{{ str_replace('_', ' ', $comm->event) }}</strong>
                                · Recipient: {{ $comm->recipient_email ?? $comm->recipient_phone ?? 'N/A' }}
                                · Channel: {{ strtoupper((string) $comm->channel) }}
                            </div>
                            <div class="small text-secondary">Sent: {{ $comm->sent_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            @if ($comm->error_message)
                                <div class="small text-danger mt-1">
                                    Failed reason (safe): {{ BookingManagementController::summarizeFailure($comm->error_message) }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">No communication logs yet.</p>
                    @endforelse

                    <h4 class="mb-2 mt-3">Emails sent</h4>
                    @php $emailLogs = $commLogs->filter(fn ($c) => strtoupper((string) $c->channel) === 'EMAIL'); @endphp
                    @if ($emailLogs->isEmpty())
                        <p class="small text-secondary mb-2">No email logs found.</p>
                    @else
                        @foreach ($emailLogs->take(6) as $comm)
                            <div class="small text-secondary mb-1">{{ $comm->sent_at?->format('Y-m-d H:i') ?? '—' }} · {{ $comm->recipient_email ?? 'N/A' }} · {{ $comm->event }}</div>
                        @endforeach
                    @endif

                    <h4 class="mb-2 mt-3">Customer notifications</h4>
                    @if ($customerLogs->isEmpty())
                        <p class="small text-secondary mb-2">No customer notification logs found.</p>
                    @else
                        @foreach ($customerLogs->take(6) as $comm)
                            <div class="small text-secondary mb-1">{{ strtoupper($comm->channel) }} · {{ $comm->recipient_email ?? $comm->recipient_phone ?? 'N/A' }} · {{ $comm->event }} · <span class="text-capitalize">{{ $comm->status }}</span></div>
                        @endforeach
                    @endif

                    <h4 class="mb-2 mt-3">Admin notifications</h4>
                    @if ($adminLogs->isEmpty())
                        <p class="small text-secondary mb-2">No admin notification logs found.</p>
                    @else
                        @foreach ($adminLogs->take(6) as $comm)
                            <div class="small text-secondary mb-1">{{ strtoupper($comm->channel) }} · {{ $comm->event }} · <span class="text-capitalize">{{ $comm->status }}</span></div>
                        @endforeach
                    @endif

                    <h4 class="mb-2 mt-3">Failed notifications</h4>
                    @if ($failedLogs->isEmpty())
                        <p class="small text-secondary mb-0">No failed notifications.</p>
                    @else
                        @foreach ($failedLogs->take(8) as $comm)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $comm->event }}</strong>
                                    <span class="text-danger text-capitalize">{{ $comm->status }}</span>
                                </div>
                                <div class="small text-secondary">{{ strtoupper((string) $comm->channel) }} · {{ $comm->recipient_email ?? $comm->recipient_phone ?? 'N/A' }}</div>
                                <div class="small text-danger mt-1">{{ BookingManagementController::summarizeFailure($comm->error_message) ?: 'No error summary available.' }}</div>
                                @if ($communicationSendUrl && filled($comm->recipient_email) && filled($comm->subject) && filled($comm->message))
                                    <form method="post" action="{{ route('admin.bookings.communication.resend', [$booking, $comm]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary mt-2">Resend failed notification</button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" disabled>Resend failed notification</button>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="card mb-3" data-tab-section="communication">
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

            <div class="card mb-3" data-tab-section="communication">
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
                <div class="card" data-tab-section="communication" id="assign-staff-panel">
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

@push('scripts')
<script>
    (function () {
        var tabsRoot = document.querySelector('[data-booking-tabs]');
        var container = document.querySelector('[data-booking-tab-container]');
        if (!tabsRoot || !container) return;

        var buttons = Array.prototype.slice.call(tabsRoot.querySelectorAll('[data-tab-target]'));
        var sections = Array.prototype.slice.call(container.querySelectorAll('[data-tab-section]'));

        function activateTab(tab) {
            var target = tab || 'overview';
            buttons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-tab-target') === target);
            });
            sections.forEach(function (sec) {
                sec.classList.toggle('booking-tab-hidden', sec.getAttribute('data-tab-section') !== target);
            });
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', target);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}
        }

        tabsRoot.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-tab-target]');
            if (!btn) return;
            activateTab(btn.getAttribute('data-tab-target'));
        });

        var initial = 'overview';
        try {
            var url = new URL(window.location.href);
            var tabParam = (url.searchParams.get('tab') || '').trim();
            if (tabParam !== '') initial = tabParam;
        } catch (e) {}
        activateTab(initial);
    })();
</script>
@endpush
