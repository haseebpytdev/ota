<?php

namespace App\Services\Reports;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingRefund;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Request $request): array
    {
        $filters = [
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'channel' => $request->string('channel')->toString() ?: 'all',
            'supplier' => $request->string('supplier')->toString() ?: 'all',
        ];

        $baseQuery = $this->scopedBookingsQuery($user);
        $this->applyFilters($baseQuery, $filters);
        $hasLiveData = (clone $baseQuery)->exists();

        if (! $hasLiveData) {
            return [
                'hasLiveData' => false,
                'filters' => $filters,
                'summary' => [
                    'gross_sales' => 0,
                    'net_revenue' => 0,
                    'total_bookings' => 0,
                    'ticketed_bookings' => 0,
                    'pending_bookings' => 0,
                    'cancelled_bookings' => 0,
                    'agent_sales' => 0,
                    'direct_customer_sales' => 0,
                    'refund_paid_amount' => 0,
                    'pending_refund_count' => 0,
                    'cancellation_count' => 0,
                ],
                'monthlySales' => collect(),
                'topRoutes' => collect(),
                'topAgents' => collect(),
                'paymentBreakdown' => collect(),
            ];
        }

        $summary = [
            'gross_sales' => $this->sumFare((clone $baseQuery), 'fare.total'),
            'net_revenue' => $this->sumNetRevenue((clone $baseQuery)),
            'total_bookings' => (clone $baseQuery)->count(),
            'ticketed_bookings' => (clone $baseQuery)->where('status', BookingStatus::Ticketed)->count(),
            'pending_bookings' => (clone $baseQuery)->where('status', BookingStatus::Pending)->count(),
            'cancelled_bookings' => (clone $baseQuery)->where('status', BookingStatus::Cancelled)->count(),
            'agent_sales' => $this->sumFare((clone $baseQuery)->whereNotNull('bookings.agent_id'), 'fare.total'),
            'direct_customer_sales' => $this->sumFare((clone $baseQuery)->whereNull('bookings.agent_id'), 'fare.total'),
            'refund_paid_amount' => $this->refundPaidAmount($user),
            'pending_refund_count' => $this->pendingRefundCount($user),
            'cancellation_count' => (clone $baseQuery)->whereNotNull('bookings.cancellation_status')->count(),
        ];

        $monthExpr = $this->monthExpression('bookings.created_at');
        $monthlySales = (clone $baseQuery)
            ->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id')
            ->selectRaw("{$monthExpr} as month")
            ->selectRaw('COUNT(bookings.id) as bookings')
            ->selectRaw('COALESCE(SUM(fare.total), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(COALESCE(fare.markup, 0) + COALESCE(fare.fees, 0) - COALESCE(fare.discount, 0)), 0) as net_revenue')
            ->groupBy(DB::raw($monthExpr))
            ->orderBy('month')
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'month' => (string) $row->month,
                'bookings' => (int) $row->bookings,
                'gross_sales' => (float) $row->gross_sales,
                'net_revenue' => (float) $row->net_revenue,
            ]);

        $topRoutes = (clone $baseQuery)
            ->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id')
            ->selectRaw("COALESCE(NULLIF(bookings.route, ''), 'Unknown route') as route")
            ->selectRaw('COUNT(bookings.id) as bookings')
            ->selectRaw('COALESCE(SUM(fare.total), 0) as sales')
            ->selectRaw('COALESCE(AVG(fare.total), 0) as average_ticket')
            ->groupBy(DB::raw("COALESCE(NULLIF(bookings.route, ''), 'Unknown route')"))
            ->orderByDesc('sales')
            ->limit(10)
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'route' => (string) $row->route,
                'bookings' => (int) $row->bookings,
                'sales' => (float) $row->sales,
                'average_ticket' => (float) $row->average_ticket,
            ]);

        $topAgents = (clone $baseQuery)
            ->whereNotNull('bookings.agent_id')
            ->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id')
            ->leftJoin('agents', 'agents.id', '=', 'bookings.agent_id')
            ->leftJoin('users as agent_users', 'agent_users.id', '=', 'agents.user_id')
            ->selectRaw('bookings.agent_id as agent_id')
            ->selectRaw('agents.code as agent_code')
            ->selectRaw('agent_users.name as agent_name')
            ->selectRaw('COUNT(bookings.id) as bookings')
            ->selectRaw('COALESCE(SUM(fare.total), 0) as sales')
            ->selectRaw('COALESCE(SUM(fare.total * COALESCE(agents.commission_percent, 0) / 100), 0) as commission')
            ->groupBy('bookings.agent_id', 'agents.code', 'agent_users.name')
            ->orderByDesc('sales')
            ->limit(10)
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'agent_code' => $row->agent_code ? (string) $row->agent_code : 'AGENT-'.$row->agent_id,
                'agent_name' => $row->agent_name ? (string) $row->agent_name : 'Unknown Agent',
                'bookings' => (int) $row->bookings,
                'sales' => (float) $row->sales,
                'commission' => (float) $row->commission,
            ]);

        $paymentBreakdown = (clone $baseQuery)
            ->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id')
            ->selectRaw("COALESCE(NULLIF(bookings.payment_status, ''), 'unpaid') as status")
            ->selectRaw('COUNT(bookings.id) as count')
            ->selectRaw('COALESCE(SUM(fare.total), 0) as amount')
            ->groupBy(DB::raw("COALESCE(NULLIF(bookings.payment_status, ''), 'unpaid')"))
            ->orderBy('status')
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'status' => str_replace('_', ' ', (string) $row->status),
                'count' => (int) $row->count,
                'amount' => (float) $row->amount,
            ]);

        return [
            'hasLiveData' => true,
            'filters' => $filters,
            'summary' => $summary,
            'monthlySales' => $monthlySales,
            'topRoutes' => $topRoutes,
            'topAgents' => $topAgents,
            'paymentBreakdown' => $paymentBreakdown,
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

    /**
     * @param  array<string, string>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['date_from'] !== '') {
            $query->whereDate('bookings.created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('bookings.created_at', '<=', $filters['date_to']);
        }

        if ($filters['channel'] === 'direct') {
            $query->whereNull('bookings.agent_id');
        } elseif ($filters['channel'] === 'agent') {
            $query->whereNotNull('bookings.agent_id');
        }

        if ($filters['supplier'] !== '' && $filters['supplier'] !== 'all') {
            $query->where('bookings.supplier', $filters['supplier']);
        }
    }

    protected function sumFare(Builder $query, string $column): float
    {
        return (float) $query
            ->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id')
            ->sum($column);
    }

    protected function sumNetRevenue(Builder $query): float
    {
        $row = $query
            ->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id')
            ->selectRaw('COALESCE(SUM(COALESCE(fare.markup, 0) + COALESCE(fare.fees, 0) - COALESCE(fare.discount, 0)), 0) as net_revenue')
            ->first();

        return (float) ($row?->net_revenue ?? 0);
    }

    protected function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
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
