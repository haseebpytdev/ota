<?php

namespace App\Services\Dashboard;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingNote;
use App\Models\BookingRefund;
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
                'count' => (clone $baseQuery)->whereNull('assigned_staff_id')->count(),
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
                'ref' => $booking->booking_reference ?? '-',
                'customer' => $booking->contact?->email ?: 'Guest',
                'route' => $booking->route ?: '-',
                'airline' => $booking->airline ?: '-',
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
            ],
            'recentBookings' => $recentBookings,
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
}
