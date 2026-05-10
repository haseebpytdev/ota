<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Agents\AgentCommissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AgentCommissionService $commissionService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Booking::class);
        $agent = auth()->user()->agent();
        $commissionBalance = $agent !== null ? $this->commissionService->calculateBalance($agent) : 0.0;

        return view('dashboard.agent.index', [
            'role' => 'Agent',
            'commissionBalance' => $commissionBalance,
        ]);
    }
}
