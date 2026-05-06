<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyUser;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AgentApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $applications = AgentApplication::query()
            ->with('reviewer')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('company_name', 'like', '%'.$search.'%');
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.admin.agent-applications.index', [
            'applications' => $applications,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    public function show(AgentApplication $application): View
    {
        return view('dashboard.admin.agent-applications.show', ['application' => $application->load('reviewer')]);
    }

    public function approve(Request $request, AgentApplication $application): RedirectResponse
    {
        $request->validate(['internal_note' => ['nullable', 'string', 'max:2000']]);
        if ($application->status === 'approved') {
            return back()->with('status', 'already-approved');
        }

        $admin = $request->user();
        $agency = $admin->currentAgency
            ?? Agency::query()->where('slug', config('ota.default_agency_slug'))->first()
            ?? Agency::query()->first();
        if ($agency === null) {
            return back()->withErrors(['application' => 'No agency is configured to onboard agent accounts yet.']);
        }

        DB::transaction(function () use ($application, $request, $admin, $agency): void {
            $user = User::query()->firstOrCreate(
                ['email' => $application->email],
                [
                    'name' => trim($application->first_name.' '.$application->last_name),
                    'password' => bcrypt(str()->random(32)),
                    'account_type' => AccountType::Agent,
                    'status' => UserAccountStatus::Invited,
                    'invited_at' => now(),
                    'current_agency_id' => $agency->id,
                    'meta' => [
                        'phone' => $application->mobile,
                        'city' => $application->city,
                        'company_name' => $application->company_name,
                    ],
                ]
            );

            $user->forceFill([
                'account_type' => AccountType::Agent,
                'status' => UserAccountStatus::Invited,
                'invited_at' => now(),
                'current_agency_id' => $agency->id,
                'meta' => array_merge($user->meta ?? [], [
                    'phone' => $application->mobile,
                    'city' => $application->city,
                    'company_name' => $application->company_name,
                ]),
            ])->save();

            AgencyUser::query()->updateOrCreate(
                ['agency_id' => $agency->id, 'user_id' => $user->id],
                ['role' => AccountType::Agent->value]
            );

            Agent::query()->updateOrCreate(
                ['user_id' => $user->id, 'agency_id' => $agency->id],
                [
                    'code' => 'AGT-'.strtoupper(str()->random(6)),
                    'commission_percent' => 0,
                    'is_active' => true,
                    'meta' => [
                        'city' => $application->city,
                        'company_name' => $application->company_name,
                        'mobile' => $application->mobile,
                    ],
                ]
            );

            Password::sendResetLink(['email' => $user->email]);

            $application->forceFill([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'internal_note' => $request->string('internal_note')->toString() ?: $application->internal_note,
            ])->save();
        });

        return back()->with('status', 'application-approved');
    }

    public function reject(Request $request, AgentApplication $application): RedirectResponse
    {
        $request->validate(['internal_note' => ['nullable', 'string', 'max:2000']]);
        $application->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'internal_note' => $request->string('internal_note')->toString() ?: $application->internal_note,
        ])->save();

        return back()->with('status', 'application-rejected');
    }

    public function needsMoreInfo(Request $request, AgentApplication $application): RedirectResponse
    {
        $request->validate(['internal_note' => ['nullable', 'string', 'max:2000']]);
        $application->forceFill([
            'status' => 'needs_more_info',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'internal_note' => $request->string('internal_note')->toString() ?: $application->internal_note,
        ])->save();

        return back()->with('status', 'application-needs-info');
    }
}
