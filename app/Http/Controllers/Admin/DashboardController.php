<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SupplierConnection;
use App\Services\Dashboard\AgencyDashboardService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AgencyDashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Booking::class);

        $data = $this->dashboardService->build(auth()->user());
        $supplierReadiness = SupplierConnection::query()
            ->where('agency_id', auth()->user()->current_agency_id)
            ->orderBy('provider')
            ->get()
            ->map(function (SupplierConnection $connection): array {
                $provider = is_string($connection->provider)
                    ? $connection->provider
                    : $connection->provider->value;

                return [
                    'name' => strtoupper($provider),
                    'code' => strtoupper($provider),
                    'readiness' => $connection->is_active ? 'connected' : 'not_configured',
                    'detail' => $connection->is_active
                        ? 'Provider configured and active.'
                        : 'Provider saved but currently disabled.',
                ];
            });

        return view('dashboard.admin.index', [
            'role' => 'Admin',
            'stats' => $data['stats'],
            'recentBookings' => $data['recentBookings'],
            'todayOperations' => $data['todayOperations'],
            'revenueSnapshot' => $data['revenueSnapshot'],
            'hasLiveData' => $data['hasLiveData'],
            'supplierReadiness' => $supplierReadiness,
        ]);
    }
}
