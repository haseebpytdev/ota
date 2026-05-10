<?php

namespace App\Services\Dashboard;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingNote;
use App\Models\BookingRefund;
use App\Models\CommunicationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AgencyDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $baseQuery = $this->scopedBookingsQuery($user);
        $hasLiveData = (clone $baseQuery)->exists();

        if (! $hasLiveData) {
            return [
                'hasLiveData' => false,
                'stats' => [
                    'total_bookings' => 0,
                    'pending_bookings' => 0,
                    'ticketed_bookings' => 0,
                    'unpaid_partial_bookings' => 0,
                    'gross_sales' => 0,
                    'markup_revenue' => 0,
                    'agent_sales' => 0,
                    'direct_customer_sales' => 0,
                    'cancellation_count' => 0,
                    'refund_amount_paid' => 0,
                    'pending_refund_count' => 0,
                ],
                'todayOperations' => $this->emptyOperations(),
                'revenueSnapshot' => [
                    'period_label' => 'No live bookings yet',
                    'direct_customer_sales' => 0,
                    'agent_sales' => 0,
                    'markup_revenue' => 0,
                ],
                'recentBookings' => collect(),
                'operationalKpis' => $this->emptyOperationalKpis(),
                'needsAttention' => $this->emptyNeedsAttention(),
                'commandSummary' => $this->emptyCommandSummary(),
                'taskActions' => $this->taskActionDefinitions(),
            ];
        }

        $stats = [
            'total_bookings' => (clone $baseQuery)->count(),
            'pending_bookings' => (clone $baseQuery)->where('status', BookingStatus::Pending)->count(),
            'ticketed_bookings' => (clone $baseQuery)->where('status', BookingStatus::Ticketed)->count(),
            'unpaid_partial_bookings' => (clone $baseQuery)->whereIn('payment_status', ['unpaid', 'partial'])->count(),
            'gross_sales' => $this->sumFareColumn($baseQuery, 'total'),
            'markup_revenue' => $this->sumFareColumn($baseQuery, 'markup'),
            'agent_sales' => $this->sumFareForChannel($baseQuery, true),
            'direct_customer_sales' => $this->sumFareForChannel($baseQuery, false),
            'cancellation_count' => (clone $baseQuery)->whereNotNull('cancellation_status')->count(),
            'refund_amount_paid' => $this->refundPaidAmount($user),
            'pending_refund_count' => $this->pendingRefundCount($user),
        ];

        $operationalCounts = [
            'needs_action' => $this->countNeedsAction($baseQuery),
            'payment_review' => $this->countPaymentReview($baseQuery),
            'supplier_pnr_pending' => $this->countSupplierPnrPending($baseQuery),
            'ticketing_pending' => $this->countTicketingPending($baseQuery),
            'unassigned' => (clone $baseQuery)->whereNull('assigned_staff_id')->count(),
            'refunds_pending' => $stats['pending_refund_count'],
            'cancellations_pending' => $this->countCancellationsPending($baseQuery),
            'today_departures' => (clone $baseQuery)->whereDate('travel_date', now()->toDateString())->count(),
            'failed_notifications' => $this->countFailedNotifications($user),
        ];

        $todayOperations = [
            [
                'title' => 'Pending bookings',
                'count' => (clone $baseQuery)->where('status', BookingStatus::Pending)->count(),
                'hint' => 'Needs follow-up',
                'route' => 'admin.bookings',
            ],
            [
                'title' => 'Fare review queue',
                'count' => (clone $baseQuery)->where('status', BookingStatus::FareReview)->count(),
                'hint' => 'Awaiting manual fare checks',
                'route' => 'admin.bookings',
            ],
            [
                'title' => 'Payment pending',
                'count' => (clone $baseQuery)->where('status', BookingStatus::PaymentPending)->count(),
                'hint' => 'Track payment completion',
                'route' => 'admin.bookings',
            ],
            [
                'title' => 'Ticketing pending',
                'count' => (clone $baseQuery)->where('status', BookingStatus::TicketingPending)->count(),
                'hint' => 'Ready for ticketing queue',
                'route' => 'admin.bookings',
            ],
            [
                'title' => 'Unassigned bookings',
                'count' => $operationalCounts['unassigned'],
                'hint' => 'Needs owner assignment',
                'route' => 'admin.bookings',
            ],
            [
                'title' => 'Internal notes today',
                'count' => $this->scopedNotesQuery($user)->whereDate('created_at', now()->toDateString())->count(),
                'hint' => 'Operational notes logged',
                'route' => 'admin.bookings',
            ],
            [
                'title' => 'Pending refunds',
                'count' => $stats['pending_refund_count'],
                'hint' => 'Awaiting approval/payout',
                'route' => 'admin.bookings',
            ],
        ];

        $recentBookings = (clone $baseQuery)
            ->with(['contact', 'fareBreakdown'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'ref' => $booking->booking_reference ?: 'Draft #'.$booking->id,
                'has_reference' => filled($booking->booking_reference),
                'preview_query' => $booking->booking_reference ?: (string) $booking->id,
                'customer' => $booking->contact?->email ?: 'Guest',
                'route' => $booking->route ?: '—',
                'airline' => $booking->airline ?: '—',
                'status' => str_replace('_', ' ', (string) $booking->status->value),
                'payment_status' => str_replace('_', ' ', (string) ($booking->payment_status ?? 'unpaid')),
                'amount_pkr' => (float) ($booking->fareBreakdown?->total ?? 0),
                'created_at' => $booking->created_at?->format('Y-m-d H:i') ?? '-',
            ]);

        return [
            'hasLiveData' => true,
            'stats' => $stats,
            'todayOperations' => $todayOperations,
            'revenueSnapshot' => [
                'period_label' => 'Live bookings to date',
                'direct_customer_sales' => $stats['direct_customer_sales'],
                'agent_sales' => $stats['agent_sales'],
                'markup_revenue' => $stats['markup_revenue'],
                'gross_sales' => $stats['gross_sales'],
            ],
            'recentBookings' => $recentBookings,
            'operationalKpis' => $this->buildOperationalKpis($operationalCounts),
            'needsAttention' => $this->buildNeedsAttention($operationalCounts),
            'commandSummary' => $this->buildCommandSummary($operationalCounts, $stats),
            'taskActions' => $this->taskActionDefinitions(),
        ];
    }

    protected function scopedBookingsQuery(User $user): Builder
    {
        $query = Booking::query();

        if (! $user->isPlatformAdmin()) {
            $query->where('bookings.agency_id', $user->current_agency_id);
        }

        return $query;
    }

    protected function scopedNotesQuery(User $user): Builder
    {
        $query = BookingNote::query();

        if (! $user->isPlatformAdmin()) {
            $query->where('agency_id', $user->current_agency_id);
        }

        return $query;
    }

    protected function sumFareColumn(Builder $baseQuery, string $column): float
    {
        $query = clone $baseQuery;

        return (float) $query
            ->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id')
            ->sum("fare.{$column}");
    }

    protected function sumFareForChannel(Builder $baseQuery, bool $agent): float
    {
        $query = clone $baseQuery;
        $query->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id');

        if ($agent) {
            $query->whereNotNull('bookings.agent_id');
        } else {
            $query->whereNull('bookings.agent_id');
        }

        return (float) $query->sum('fare.total');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function emptyOperations(): Collection
    {
        return collect([
            ['title' => 'Pending bookings', 'count' => 0, 'hint' => 'No live bookings yet', 'route' => 'admin.bookings'],
            ['title' => 'Fare review queue', 'count' => 0, 'hint' => 'No live bookings yet', 'route' => 'admin.bookings'],
            ['title' => 'Payment pending', 'count' => 0, 'hint' => 'No live bookings yet', 'route' => 'admin.bookings'],
            ['title' => 'Ticketing pending', 'count' => 0, 'hint' => 'No live bookings yet', 'route' => 'admin.bookings'],
            ['title' => 'Unassigned bookings', 'count' => 0, 'hint' => 'No live bookings yet', 'route' => 'admin.bookings'],
            ['title' => 'Internal notes today', 'count' => 0, 'hint' => 'No live bookings yet', 'route' => 'admin.bookings'],
            ['title' => 'Pending refunds', 'count' => 0, 'hint' => 'No live bookings yet', 'route' => 'admin.bookings'],
        ]);
    }

    protected function refundPaidAmount(User $user): float
    {
        $query = BookingRefund::query()->where('status', 'paid');
        if (! $user->isPlatformAdmin()) {
            $query->where('agency_id', $user->current_agency_id);
        }

        return (float) $query->sum('amount');
    }

    protected function pendingRefundCount(User $user): int
    {
        $query = BookingRefund::query()->whereIn('status', ['pending', 'approved']);
        if (! $user->isPlatformAdmin()) {
            $query->where('agency_id', $user->current_agency_id);
        }

        return (int) $query->count();
    }

    /**
     * Mirror of BookingManagementController::applyQueueFilter('needs_action').
     */
    protected function countNeedsAction(Builder $baseQuery): int
    {
        return (int) (clone $baseQuery)->where(function (Builder $inner): void {
            $inner->whereIn('payment_status', ['unpaid', 'partial'])
                ->orWhereHas('payments', function (Builder $p): void {
                    $p->whereIn('status', ['submitted', 'pending']);
                })
                ->orWhereIn('supplier_booking_status', ['failed', 'manual_review'])
                ->orWhere(function (Builder $pnr): void {
                    $pnr->where('payment_status', 'paid')
                        ->where(function (Builder $missingPnr): void {
                            $missingPnr->whereNull('pnr')->orWhere('pnr', '');
                        });
                })
                ->orWhereIn('ticketing_status', ['pending', 'not_started', 'failed'])
                ->orWhereHas('cancellationRequests', function (Builder $c): void {
                    $c->whereIn('status', ['requested', 'approved']);
                })
                ->orWhereHas('refunds', function (Builder $r): void {
                    $r->whereIn('status', ['pending', 'approved']);
                });
        })->count();
    }

    protected function countPaymentReview(Builder $baseQuery): int
    {
        return (int) (clone $baseQuery)->whereIn('payment_status', ['unpaid', 'partial'])->count();
    }

    protected function countSupplierPnrPending(Builder $baseQuery): int
    {
        return (int) (clone $baseQuery)->where(function (Builder $inner): void {
            $inner->where(function (Builder $paidNoPnr): void {
                $paidNoPnr->where('payment_status', 'paid')
                    ->where(function (Builder $missingPnr): void {
                        $missingPnr->whereNull('pnr')->orWhere('pnr', '');
                    });
            })->orWhereIn('supplier_booking_status', ['failed', 'manual_review']);
        })->count();
    }

    protected function countTicketingPending(Builder $baseQuery): int
    {
        return (int) (clone $baseQuery)->where(function (Builder $inner): void {
            $inner->where('payment_status', 'paid')
                ->where(function (Builder $pnr): void {
                    $pnr->whereNotNull('pnr')->where('pnr', '<>', '');
                })
                ->where(function (Builder $notTicketed): void {
                    $notTicketed->whereNull('ticketed_at')
                        ->orWhereNotIn('ticketing_status', ['ticketed', 'issued']);
                });
        })->count();
    }

    protected function countCancellationsPending(Builder $baseQuery): int
    {
        return (int) (clone $baseQuery)->whereHas('cancellationRequests', function (Builder $c): void {
            $c->whereIn('status', ['requested', 'approved']);
        })->count();
    }

    protected function countFailedNotifications(User $user): int
    {
        $query = CommunicationLog::query()->whereIn('status', ['failed', 'error']);
        if (! $user->isPlatformAdmin()) {
            $query->where('agency_id', $user->current_agency_id);
        }

        return (int) $query->count();
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    protected function buildOperationalKpis(array $counts): array
    {
        return [
            [
                'key' => 'needs_action',
                'label' => 'Needs action',
                'count' => $counts['needs_action'],
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'needs_action'],
                'tone' => 'warning',
                'icon' => 'ti-alert-triangle',
                'helper' => 'Items requiring an operator response.',
            ],
            [
                'key' => 'payment_review',
                'label' => 'Payment review',
                'count' => $counts['payment_review'],
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'payment_review'],
                'tone' => 'info',
                'icon' => 'ti-cash',
                'helper' => 'Unpaid or partial balances.',
            ],
            [
                'key' => 'supplier_pnr_pending',
                'label' => 'Supplier / PNR pending',
                'count' => $counts['supplier_pnr_pending'],
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'supplier_pnr'],
                'tone' => 'primary',
                'icon' => 'ti-plug-connected',
                'helper' => 'Paid bookings awaiting a PNR.',
            ],
            [
                'key' => 'ticketing_pending',
                'label' => 'Ticketing pending',
                'count' => $counts['ticketing_pending'],
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'ticketing'],
                'tone' => 'success',
                'icon' => 'ti-ticket',
                'helper' => 'Ready for ticket issuance.',
            ],
            [
                'key' => 'unassigned',
                'label' => 'Unassigned bookings',
                'count' => $counts['unassigned'],
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'all'],
                'tone' => 'muted',
                'icon' => 'ti-user-off',
                'helper' => 'No staff owner yet.',
            ],
            [
                'key' => 'refunds_pending',
                'label' => 'Refunds pending',
                'count' => $counts['refunds_pending'],
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'refunds'],
                'tone' => 'danger',
                'icon' => 'ti-receipt-refund',
                'helper' => 'Awaiting approval or payout.',
            ],
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    protected function buildNeedsAttention(array $counts): array
    {
        return [
            [
                'key' => 'unassigned',
                'label' => 'Unassigned bookings',
                'count' => $counts['unassigned'],
                'helper' => 'Assign an owner to keep work flowing.',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'all'],
            ],
            [
                'key' => 'payment_review',
                'label' => 'Payment review',
                'count' => $counts['payment_review'],
                'helper' => 'Confirm payment proofs and balances.',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'payment_review'],
            ],
            [
                'key' => 'supplier_pnr_pending',
                'label' => 'Supplier PNR pending',
                'count' => $counts['supplier_pnr_pending'],
                'helper' => 'Create or attach a supplier PNR.',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'supplier_pnr'],
            ],
            [
                'key' => 'ticketing_pending',
                'label' => 'Ticketing pending',
                'count' => $counts['ticketing_pending'],
                'helper' => 'Issue tickets for ready bookings.',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'ticketing'],
            ],
            [
                'key' => 'cancellations_pending',
                'label' => 'Cancellation requests',
                'count' => $counts['cancellations_pending'],
                'helper' => 'Requests awaiting decision.',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'cancellations'],
            ],
            [
                'key' => 'refunds_pending',
                'label' => 'Refund requests',
                'count' => $counts['refunds_pending'],
                'helper' => 'Approve or pay out approved refunds.',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'refunds'],
            ],
            [
                'key' => 'failed_notifications',
                'label' => 'Failed notifications',
                'count' => $counts['failed_notifications'],
                'helper' => 'Communications that need a retry.',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'all'],
            ],
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>
     */
    protected function buildCommandSummary(array $counts, array $stats): array
    {
        return [
            'needs_action' => $counts['needs_action'],
            'payment_review' => $counts['payment_review'],
            'ticketing_pending' => $counts['ticketing_pending'],
            'today_departures' => $counts['today_departures'],
            'gross_sales' => (float) ($stats['gross_sales'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function taskActionDefinitions(): array
    {
        return [
            [
                'key' => 'review_new_bookings',
                'label' => 'Review new bookings',
                'helper' => 'Triage incoming work',
                'icon' => 'ti-inbox',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'needs_action'],
            ],
            [
                'key' => 'record_payment',
                'label' => 'Record payment',
                'helper' => 'Verify proofs & balances',
                'icon' => 'ti-cash',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'payment_review'],
            ],
            [
                'key' => 'create_supplier_pnr',
                'label' => 'Create supplier PNR',
                'helper' => 'Push paid bookings to suppliers',
                'icon' => 'ti-plug-connected',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'supplier_pnr'],
            ],
            [
                'key' => 'issue_tickets',
                'label' => 'Issue tickets',
                'helper' => 'Confirm tickets for ready bookings',
                'icon' => 'ti-ticket',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'ticketing'],
            ],
            [
                'key' => 'generate_invoices',
                'label' => 'Generate invoices',
                'helper' => 'Bookings missing invoices',
                'icon' => 'ti-file-invoice',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'invoices'],
            ],
            [
                'key' => 'handle_refunds',
                'label' => 'Handle refunds',
                'helper' => 'Approve or pay out refunds',
                'icon' => 'ti-receipt-refund',
                'route' => 'admin.bookings',
                'route_params' => ['queue' => 'refunds'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function emptyOperationalKpis(): array
    {
        return $this->buildOperationalKpis([
            'needs_action' => 0,
            'payment_review' => 0,
            'supplier_pnr_pending' => 0,
            'ticketing_pending' => 0,
            'unassigned' => 0,
            'refunds_pending' => 0,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function emptyNeedsAttention(): array
    {
        return $this->buildNeedsAttention([
            'unassigned' => 0,
            'payment_review' => 0,
            'supplier_pnr_pending' => 0,
            'ticketing_pending' => 0,
            'cancellations_pending' => 0,
            'refunds_pending' => 0,
            'failed_notifications' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyCommandSummary(): array
    {
        return [
            'needs_action' => 0,
            'payment_review' => 0,
            'ticketing_pending' => 0,
            'today_departures' => 0,
            'gross_sales' => 0,
        ];
    }
}
