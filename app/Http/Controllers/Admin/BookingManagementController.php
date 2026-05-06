<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Services\Suppliers\SupplierBookingService;
use App\Services\Suppliers\TicketingService;
use App\Support\Bookings\BookingListPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class BookingManagementController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected SupplierBookingService $supplierBookingService,
        protected TicketingService $ticketingService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Booking::class);

        $user = auth()->user();

        $baseQuery = $this->scopedBookingsQuery($user);
        $this->applyListFilters($baseQuery, $request, $user);

        $kpis = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', BookingStatus::Pending)->count(),
            'ticketed' => (clone $baseQuery)->where('status', BookingStatus::Ticketed)->count(),
            'unpaid' => (clone $baseQuery)->whereIn('payment_status', ['unpaid', 'partial'])->count(),
        ];

        /** @var LengthAwarePaginator<int, array<string, mixed>> $paginator */
        $paginator = (clone $baseQuery)->paginate(25)->withQueryString();
        $mappedRows = $paginator->getCollection()->map(fn (Booking $booking): array => BookingListPresenter::toListRow($booking));
        $paginator->setCollection($mappedRows);

        $previewParam = $request->string('preview')->toString();
        $selectedBooking = null;
        $selectedPreviewKey = '';

        if ($previewParam !== '') {
            $match = ctype_digit($previewParam)
                ? (clone $baseQuery)->whereKey((int) $previewParam)->first()
                : (clone $baseQuery)->where('booking_reference', $previewParam)->first();

            if ($match === null) {
                abort(403);
            }

            Gate::authorize('view', $match);
            $selectedBooking = BookingListPresenter::toListRow($match);
            $selectedPreviewKey = (string) $selectedBooking['preview_query'];
        } else {
            $first = (clone $baseQuery)->first();
            if ($first !== null) {
                Gate::authorize('view', $first);
                $selectedBooking = BookingListPresenter::toListRow($first);
                $selectedPreviewKey = (string) $selectedBooking['preview_query'];
            }
        }

        $filterStaff = $this->assignableUsersForAgency($user, null);

        return view('dashboard.admin.bookings', [
            'bookings' => $paginator,
            'kpis' => $kpis,
            'selectedBooking' => $selectedBooking,
            'previewRef' => $previewParam,
            'selectedPreviewKey' => $selectedPreviewKey,
            'usingDatabase' => true,
            'hasRows' => $mappedRows->isNotEmpty(),
            'filters' => $request->only(['search', 'status', 'payment_status', 'date_from', 'date_to', 'assigned_staff_id']),
            'filterStaffUsers' => $filterStaff,
            'statusEnumCases' => BookingStatus::cases(),
        ]);
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load([
            'passengers',
            'contact',
            'fareBreakdown',
            'agent.user',
            'assignedStaff',
            'bookingNotes.user',
            'statusLogs.user',
            'supplierBookingAttempts.attemptedBy',
            'supplierBookings.createdBy',
            'latestSupplierBooking',
            'tickets.passenger',
            'tickets.issuedBy',
            'ticketingAttempts.attemptedBy',
            'latestTicketingAttempt',
            'payments.payer',
            'payments.receiver',
            'payments.documents',
            'communicationLogs',
            'documents.generatedBy',
            'cancellationRequests.requester',
            'cancellationRequests.approver',
            'cancellationRequests.processor',
            'refunds.approver',
            'refunds.payer',
        ]);

        $allowed = $this->bookingService->getAllowedStatusTransitions($booking, auth()->user());

        $auditLogs = AuditLog::query()
            ->where('auditable_type', Booking::class)
            ->where('auditable_id', $booking->id)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $assignableStaff = $this->assignableUsersForAgency(auth()->user(), $booking);

        return view('dashboard.admin.bookings.show', [
            'booking' => $booking,
            'portal' => 'admin',
            'allowedTransitions' => $allowed,
            'assignableStaff' => $assignableStaff,
            'auditLogs' => $auditLogs,
            'supplierBookingEligible' => $this->supplierBookingService->isBookingEligible($booking),
            'ticketingEligible' => $this->ticketingService->isBookingEligibleForTicketing($booking),
        ]);
    }

    public function createSupplierBooking(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('createSupplierBooking', $booking);

        $result = $this->supplierBookingService->createSupplierBooking($booking, $request->user());
        if (! $result->success) {
            return back()->withErrors([
                'supplier_booking' => $result->error_message ?: ($result->warnings[0] ?? 'Supplier booking could not be created.'),
            ]);
        }

        return back()->with('status', 'supplier-booking-created');
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('changeStatus', $booking);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $to = BookingStatus::from($validated['status']);

        try {
            $this->bookingService->changeStatus(
                $booking,
                $to,
                $request->user(),
                $validated['note'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'booking-status-updated');
    }

    public function storeNote(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('addNote', $booking);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:10000'],
            'is_customer_visible' => ['sometimes', 'boolean'],
        ]);

        $this->bookingService->addInternalNote(
            $booking,
            $request->user(),
            $validated['note'],
            (bool) ($validated['is_customer_visible'] ?? false),
        );

        return back()->with('status', 'note-added');
    }

    public function assignStaff(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('assignStaff', $booking);

        $validated = $request->validate([
            'staff_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $assignee = isset($validated['staff_user_id'])
            ? User::query()->find($validated['staff_user_id'])
            : null;

        try {
            $this->bookingService->assignStaff($booking, $assignee, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['staff_user_id' => $e->getMessage()]);
        }

        return back()->with('status', 'staff-assigned');
    }

    protected function scopedBookingsQuery(User $user): Builder
    {
        $q = Booking::query()
            ->with(['passengers', 'contact', 'fareBreakdown', 'agent.user', 'assignedStaff'])
            ->orderByDesc('created_at');

        if (! $user->isPlatformAdmin()) {
            $q->where('agency_id', $user->current_agency_id);
        }

        return $q;
    }

    protected function applyListFilters(Builder $q, Request $request, User $user): void
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $q->where(function (Builder $inner) use ($search): void {
                $inner->where('booking_reference', 'like', '%'.$search.'%')
                    ->orWhereHas('passengers', function (Builder $p) use ($search): void {
                        $p->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('contact', function (Builder $c) use ($search): void {
                        $c->where('email', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }

        if ($request->filled('payment_status')) {
            $q->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date('date_to'));
        }

        if ($request->filled('assigned_staff_id')) {
            $q->where('assigned_staff_id', $request->integer('assigned_staff_id'));
        }
    }

    /**
     * @return Collection<int, User>
     */
    protected function assignableUsersForAgency(User $actor, ?Booking $booking): Collection
    {
        if ($actor->isPlatformAdmin()) {
            if ($booking !== null) {
                $agencyId = $booking->agency_id;
            } else {
                return User::query()
                    ->whereIn('account_type', [AccountType::Staff, AccountType::AgencyAdmin])
                    ->orderBy('name')
                    ->limit(500)
                    ->get();
            }
        } else {
            $agencyId = $actor->current_agency_id;
        }

        if (! isset($agencyId) || $agencyId === null) {
            return collect();
        }

        return User::query()
            ->where('current_agency_id', $agencyId)
            ->where(function (Builder $q): void {
                $q->where('account_type', AccountType::Staff)
                    ->orWhere('account_type', AccountType::AgencyAdmin);
            })
            ->orderBy('name')
            ->get();
    }
}
