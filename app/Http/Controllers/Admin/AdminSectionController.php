<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentCommissionEntry;
use App\Models\Booking;
use App\Models\StaffProfile;
use App\Models\SupplierConnection;
use App\Services\Reports\BookingReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminSectionController extends Controller
{
    public function __construct(
        protected BookingReportService $bookingReportService,
    ) {}

    public function agents(Request $request): View
    {
        Gate::authorize('viewAny', Agent::class);

        $agentsQuery = Agent::query()
            ->with(['agency', 'user', 'bookings.fareBreakdown', 'commissionEntries'])
            ->when(! $request->user()->isPlatformAdmin(), function (Builder $query) use ($request): void {
                $query->where('agency_id', $request->user()->current_agency_id);
            });

        $search = $request->string('search')->toString();
        $city = $request->string('city')->toString();
        $status = $request->string('status')->toString();

        if ($search !== '') {
            $agentsQuery->where(function (Builder $query) use ($search): void {
                $query->where('code', 'like', '%'.$search.'%')
                    ->orWhereHas('agency', fn (Builder $q): Builder => $q->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('user', function (Builder $q) use ($search): void {
                        $q->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('meta->phone', 'like', '%'.$search.'%');
                    });
            });
        }
        if ($city !== '') {
            $agentsQuery->where('meta->city', $city);
        }
        if ($status !== '') {
            $agentsQuery->where('is_active', $status === 'active');
        }

        /** @var Collection<int, Agent> $agents */
        $agents = $agentsQuery->orderByDesc('id')->get();
        $agentRows = $agents->map(function (Agent $agent): array {
            $bookings = $agent->bookings;
            $monthlySales = (float) $bookings
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum(fn (Booking $booking): float => (float) ($booking->fareBreakdown?->total ?? 0));
            $outstanding = (float) $agent->commissionEntries
                ->whereIn('status', ['approved', 'pending'])
                ->sum('commission_amount');

            return [
                'id' => $agent->id,
                'agent_code' => $agent->code ?: ('AGT-'.$agent->id),
                'agency_name' => $agent->agency?->name ?? '—',
                'contact_person' => $agent->user?->name ?? '—',
                'email' => $agent->user?->email ?? '—',
                'phone' => (string) ($agent->user?->meta['phone'] ?? '—'),
                'city' => (string) ($agent->meta['city'] ?? '—'),
                'commission_percent' => (float) ($agent->commission_percent ?? 0),
                'commission_plan' => (string) ($agent->meta['commission_plan'] ?? (($agent->commission_percent ?? 0).'%')),
                'bookings_count' => $bookings->count(),
                'monthly_sales' => $monthlySales,
                'outstanding_balance' => $outstanding,
                'status' => $agent->is_active ? 'active' : 'inactive',
                'commission_entries_count' => $agent->commissionEntries->count(),
                'recent_bookings' => $bookings->sortByDesc('created_at')->take(5)->map(fn (Booking $booking): array => [
                    'reference' => (string) ($booking->booking_reference ?? ('#'.$booking->id)),
                    'route' => (string) ($booking->route ?? '—'),
                    'amount' => (float) ($booking->fareBreakdown?->total ?? 0),
                    'status' => $booking->status->value,
                ])->values()->all(),
                'notes' => (string) ($agent->meta['notes'] ?? 'No notes added yet.'),
            ];
        })->values();

        $kpis = [
            'total' => $agentRows->count(),
            'active' => $agentRows->where('status', 'active')->count(),
            'inactive' => $agentRows->where('status', 'inactive')->count(),
            'monthly_sales' => (float) $agentRows->sum('monthly_sales'),
            'outstanding' => (float) $agentRows->sum('outstanding_balance'),
        ];
        $cities = $agentRows->pluck('city')->filter(fn (string $cityValue): bool => $cityValue !== '—')->unique()->sort()->values();

        $preview = $request->string('preview')->toString();
        $selectedAgent = $agentRows->first(fn (array $row): bool => (string) $row['id'] === $preview || (string) $row['agent_code'] === $preview)
            ?? $agentRows->first();

        return view('dashboard.admin.agents', [
            'agents' => $agentRows,
            'kpis' => $kpis,
            'cities' => $cities,
            'selectedAgent' => $selectedAgent,
            'previewCode' => $preview,
            'filters' => [
                'search' => $search,
                'city' => $city,
                'status' => $status,
            ],
        ]);
    }

    public function staff(Request $request): View
    {
        Gate::authorize('viewAny', StaffProfile::class);

        $staffQuery = StaffProfile::query()
            ->with(['agency', 'user'])
            ->when(! $request->user()->isPlatformAdmin(), function (Builder $query) use ($request): void {
                $query->where('agency_id', $request->user()->current_agency_id);
            });

        $search = $request->string('search')->toString();
        $department = $request->string('department')->toString();
        $status = $request->string('status')->toString();

        if ($search !== '') {
            $staffQuery->where(function (Builder $query) use ($search): void {
                $query->where('job_title', 'like', '%'.$search.'%')
                    ->orWhere('department', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function (Builder $q) use ($search): void {
                        $q->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }
        if ($department !== '') {
            $staffQuery->where('department', $department);
        }
        if ($status !== '') {
            $staffQuery->whereHas('user', fn (Builder $query): Builder => $query->where('status', $status));
        }

        /** @var Collection<int, StaffProfile> $staffProfiles */
        $staffProfiles = $staffQuery->orderByDesc('id')->get();
        $staffRows = $staffProfiles->map(function (StaffProfile $profile): array {
            $assignedBookings = Booking::query()->where('assigned_staff_id', $profile->user_id)->count();
            $recentBookings = Booking::query()
                ->where('assigned_staff_id', $profile->user_id)
                ->latest('id')
                ->limit(5)
                ->get(['id', 'booking_reference', 'route', 'status']);

            $status = $profile->user?->status?->value ?? UserAccountStatus::Inactive->value;

            return [
                'id' => $profile->id,
                'staff_code' => 'STF-'.$profile->id,
                'name' => $profile->user?->name ?? '—',
                'email' => $profile->user?->email ?? '—',
                'job_title' => (string) ($profile->job_title ?? '—'),
                'department' => (string) ($profile->department ?? 'General'),
                'assigned_bookings' => $assignedBookings,
                'status' => $status,
                'last_login_at' => $profile->user?->last_login_at?->format('Y-m-d H:i') ?? 'Never',
                'recent_bookings' => $recentBookings->map(fn (Booking $booking): array => [
                    'reference' => (string) ($booking->booking_reference ?? ('#'.$booking->id)),
                    'route' => (string) ($booking->route ?? '—'),
                    'status' => $booking->status->value,
                ])->all(),
            ];
        })->values();

        $kpis = [
            'total' => $staffRows->count(),
            'active' => $staffRows->where('status', UserAccountStatus::Active->value)->count(),
            'inactive' => $staffRows->whereIn('status', [UserAccountStatus::Inactive->value, UserAccountStatus::Suspended->value])->count(),
            'assigned_bookings' => (int) $staffRows->sum('assigned_bookings'),
        ];

        $departments = $staffRows->pluck('department')->unique()->sort()->values();
        $preview = $request->string('preview')->toString();
        $selectedStaff = $staffRows->first(fn (array $row): bool => (string) $row['id'] === $preview || (string) $row['staff_code'] === $preview)
            ?? $staffRows->first();

        return view('dashboard.admin.staff', [
            'staff' => $staffRows,
            'kpis' => $kpis,
            'departments' => $departments,
            'selectedStaff' => $selectedStaff,
            'previewCode' => $preview,
            'filters' => [
                'search' => $search,
                'department' => $department,
                'status' => $status,
            ],
        ]);
    }

    public function apiSettings(): View
    {
        Gate::authorize('viewAny', SupplierConnection::class);

        $config = config('ota-suppliers', []);

        return view('dashboard.admin.api-settings', [
            'suppliers' => $config['suppliers'] ?? [],
            'integrationNotice' => $config['integration_notice'] ?? '',
        ]);
    }

    public function rolesPermissions(): View
    {
        $matrix = [
            ['area' => 'Admin dashboard', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Denied', 'agent' => 'Denied', 'customer' => 'Denied'],
            ['area' => 'Staff portal', 'platform_admin' => 'Denied', 'agency_admin' => 'Denied', 'staff' => 'Allowed', 'agent' => 'Denied', 'customer' => 'Denied'],
            ['area' => 'Agent portal', 'platform_admin' => 'Denied', 'agency_admin' => 'Denied', 'staff' => 'Denied', 'agent' => 'Allowed', 'customer' => 'Denied'],
            ['area' => 'Customer portal', 'platform_admin' => 'Denied', 'agency_admin' => 'Denied', 'staff' => 'Denied', 'agent' => 'Denied', 'customer' => 'Allowed'],
            ['area' => 'Bookings', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Allowed', 'agent' => 'Limited', 'customer' => 'Limited'],
            ['area' => 'Payments', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Limited', 'agent' => 'Limited', 'customer' => 'Limited'],
            ['area' => 'Ticketing', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Limited', 'agent' => 'Denied', 'customer' => 'Denied'],
            ['area' => 'Documents', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Allowed', 'agent' => 'Limited', 'customer' => 'Limited'],
            ['area' => 'Users & Access', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Denied', 'agent' => 'Denied', 'customer' => 'Denied'],
            ['area' => 'Branding/settings', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Denied', 'agent' => 'Denied', 'customer' => 'Denied'],
            ['area' => 'Commissions', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Limited', 'agent' => 'Limited', 'customer' => 'Denied'],
            ['area' => 'Reports', 'platform_admin' => 'Allowed', 'agency_admin' => 'Allowed', 'staff' => 'Denied', 'agent' => 'Denied', 'customer' => 'Denied'],
        ];

        return view('dashboard.admin.roles-permissions', [
            'accountTypes' => array_map(
                fn (AccountType $type): array => ['key' => $type->value, 'label' => Str::headline(str_replace('_', ' ', $type->value))],
                AccountType::cases(),
            ),
            'matrix' => $matrix,
        ]);
    }

    public function reports(Request $request): View
    {
        Gate::authorize('viewAny', Booking::class);
        $report = $this->bookingReportService->build($request->user(), $request);
        $agencyId = $request->user()->current_agency_id;
        $commissionTotals = [
            'approved' => (float) AgentCommissionEntry::query()->where('agency_id', $agencyId)->where('status', 'approved')->sum('commission_amount'),
            'paid' => (float) AgentCommissionEntry::query()->where('agency_id', $agencyId)->where('status', 'paid')->sum('commission_amount'),
        ];

        return view('dashboard.admin.reports', [
            'summary' => $report['summary'],
            'monthlySales' => $report['monthlySales'],
            'topRoutes' => $report['topRoutes'],
            'topAgents' => $report['topAgents'],
            'paymentBreakdown' => $report['paymentBreakdown'],
            'filters' => $report['filters'],
            'hasLiveData' => $report['hasLiveData'],
            'commissionTotals' => $commissionTotals,
        ]);
    }

    public function branding(): RedirectResponse
    {
        return redirect()->route('admin.settings.branding.edit');
    }

    public function goLiveChecklist(): View
    {
        $items = collect(config('ota-go-live.items', []))
            ->map(function (array $item): array {
                $note = (string) ($item['note'] ?? '');
                $cleanNote = str_ireplace(
                    ['demo', 'sample', 'placeholder', 'fake'],
                    ['deployment', 'production', 'not configured', ''],
                    $note
                );

                return [
                    'label' => $item['label'] ?? 'Checklist item',
                    'note' => trim(preg_replace('/\s+/', ' ', $cleanNote) ?? ''),
                    'done' => (bool) ($item['done'] ?? false),
                ];
            })
            ->values()
            ->all();

        return view('dashboard.admin.go-live-checklist', [
            'items' => $items,
        ]);
    }
}
