<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentCommissionStatement;
use App\Services\Agents\AgentCommissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class AgentCommissionController extends Controller
{
    public function __construct(
        protected AgentCommissionService $commissionService,
    ) {}

    public function index(): View
    {
        $agent = auth()->user()->agent();
        abort_if($agent === null, 403);
        Gate::authorize('view', $agent);

        $agent->load(['commissionEntries.booking', 'commissionStatements']);
        $entries = $agent->commissionEntries->sortByDesc('created_at');

        return view('dashboard.agent.commissions.index', [
            'agent' => $agent,
            'entries' => $entries,
            'statements' => $agent->commissionStatements->sortByDesc('created_at'),
            'balance' => $this->commissionService->calculateBalance($agent),
            'pending' => (float) $entries->where('status', 'pending')->sum('commission_amount'),
            'approved' => (float) $entries->where('status', 'approved')->sum('commission_amount'),
            'paid' => (float) $entries->where('status', 'paid')->sum('commission_amount'),
        ]);
    }

    public function showStatement(AgentCommissionStatement $statement): View
    {
        Gate::authorize('view', $statement);
        $statement->load(['agent.user', 'entries.booking']);

        return view('dashboard.agent.commissions.statement', [
            'statement' => $statement,
        ]);
    }
}
