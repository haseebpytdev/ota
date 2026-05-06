<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyUser;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\CommunicationLog;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $actor = $request->user();
        $query = User::query()->with(['currentAgency']);

        if (! $actor->isPlatformAdmin()) {
            $query->where('current_agency_id', $actor->current_agency_id)
                ->where('account_type', '!=', AccountType::PlatformAdmin->value);
        }

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->string('account_type')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
        }

        $users = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $kpisQuery = clone $query;

        return view('dashboard.admin.users.index', [
            'users' => $users,
            'filters' => $request->only(['account_type', 'status', 'search']),
            'kpis' => [
                'total' => (clone $kpisQuery)->count(),
                'staff' => (clone $kpisQuery)->where('account_type', AccountType::Staff)->count(),
                'agents' => (clone $kpisQuery)->where('account_type', AccountType::Agent)->count(),
                'customers' => (clone $kpisQuery)->where('account_type', AccountType::Customer)->count(),
                'suspended_or_invited' => (clone $kpisQuery)->whereIn('status', [UserAccountStatus::Suspended, UserAccountStatus::Invited])->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        return view('dashboard.admin.users.create', [
            'userModel' => new User,
            'isEdit' => false,
            'accountTypeOptions' => $this->accountTypeOptions($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $actor = $request->user();
        $validated = $this->validatePayload($request, false, null);
        $agency = $this->resolveAgency($actor, $validated['agency_id'] ?? null);

        $user = DB::transaction(function () use ($validated, $agency, $actor): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt(str()->random(32)),
                'account_type' => $validated['account_type'],
                'current_agency_id' => $agency?->id,
                'status' => $validated['status'],
                'invited_at' => $validated['status'] === UserAccountStatus::Invited->value ? now() : null,
                'meta' => [
                    'phone' => $validated['phone'] ?? null,
                    'permission_group' => $validated['permission_group'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'agency_name' => $validated['agency_name'] ?? null,
                ],
            ]);

            if ($agency !== null) {
                AgencyUser::query()->create([
                    'agency_id' => $agency->id,
                    'user_id' => $user->id,
                    'role' => $validated['account_type'],
                ]);
            }

            $this->syncProfile($user, $validated, $agency?->id);
            $this->writeAudit($actor, 'user.created', ['user_id' => $user->id, 'account_type' => $user->account_type?->value]);

            return $user;
        });

        if ($request->boolean('send_invite')) {
            $this->dispatchInvite($user, $actor);
        }

        return redirect()->route('admin.users.show', $user)->with('status', 'user-created');
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);
        $user->load(['staffProfile', 'agentProfile.bookings', 'bookings']);

        return view('dashboard.admin.users.show', ['userModel' => $user]);
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);

        return view('dashboard.admin.users.edit', [
            'userModel' => $user->load(['staffProfile', 'agentProfile']),
            'isEdit' => true,
            'accountTypeOptions' => $this->accountTypeOptions($request->user()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $actor = $request->user();
        $validated = $this->validatePayload($request, true, $user);
        $agency = $this->resolveAgency($actor, $validated['agency_id'] ?? $user->current_agency_id);

        DB::transaction(function () use ($user, $validated, $agency, $actor): void {
            $meta = array_merge($user->meta ?? [], [
                'phone' => $validated['phone'] ?? null,
                'permission_group' => $validated['permission_group'] ?? null,
                'city' => $validated['city'] ?? null,
                'agency_name' => $validated['agency_name'] ?? null,
            ]);
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'account_type' => $validated['account_type'],
                'status' => $validated['status'],
                'current_agency_id' => $agency?->id,
                'meta' => $meta,
            ])->save();

            if ($agency !== null) {
                AgencyUser::query()->updateOrCreate(
                    ['agency_id' => $agency->id, 'user_id' => $user->id],
                    ['role' => $validated['account_type']]
                );
            }

            $this->syncProfile($user, $validated, $agency?->id);
            $this->writeAudit($actor, 'user.updated', ['user_id' => $user->id]);
        });

        return redirect()->route('admin.users.show', $user)->with('status', 'user-updated');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('suspend', $user);
        $user->forceFill(['status' => UserAccountStatus::Suspended])->save();
        $this->writeAudit($request->user(), 'user.suspended', ['user_id' => $user->id]);

        return back()->with('status', 'user-suspended');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('activate', $user);
        $user->forceFill(['status' => UserAccountStatus::Active])->save();
        $this->writeAudit($request->user(), 'user.activated', ['user_id' => $user->id]);

        return back()->with('status', 'user-activated');
    }

    public function sendInvite(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $this->dispatchInvite($user, $request->user());

        return back()->with('status', 'user-invite-sent');
    }

    public function sendResetPasswordLink(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        Password::sendResetLink(['email' => $user->email]);
        CommunicationLog::query()->create([
            'agency_id' => $user->current_agency_id,
            'user_id' => $user->id,
            'channel' => 'system',
            'event' => 'password_reset_requested',
            'recipient_name' => $user->name,
            'recipient_email' => $user->email,
            'status' => 'sent',
            'meta' => ['requested_by' => $request->user()->id],
            'sent_at' => now(),
        ]);
        $this->writeAudit($request->user(), 'user.password_reset_requested', ['user_id' => $user->id]);

        return back()->with('status', 'password-reset-link-sent');
    }

    protected function dispatchInvite(User $user, User $actor): void
    {
        $user->forceFill(['status' => UserAccountStatus::Invited, 'invited_at' => now()])->save();
        Password::sendResetLink(['email' => $user->email]);
        CommunicationLog::query()->create([
            'agency_id' => $user->current_agency_id,
            'user_id' => $user->id,
            'channel' => 'system',
            'event' => 'user_invited',
            'recipient_name' => $user->name,
            'recipient_email' => $user->email,
            'status' => 'sent',
            'meta' => ['invited_by' => $actor->id],
            'sent_at' => now(),
        ]);
        $this->writeAudit($actor, 'user.invited', ['user_id' => $user->id]);
    }

    /**
     * @return array<int, string>
     */
    protected function accountTypeOptions(User $actor): array
    {
        $base = [
            AccountType::AgencyAdmin->value,
            AccountType::Staff->value,
            AccountType::Agent->value,
            AccountType::Customer->value,
        ];

        return $actor->isPlatformAdmin()
            ? [AccountType::PlatformAdmin->value, ...$base]
            : $base;
    }

    protected function resolveAgency(User $actor, ?int $requestedAgencyId): ?Agency
    {
        if ($requestedAgencyId === null) {
            return $actor->currentAgency;
        }

        if ($actor->isPlatformAdmin()) {
            return Agency::query()->find($requestedAgencyId);
        }

        if ($actor->current_agency_id !== $requestedAgencyId) {
            abort(403);
        }

        return Agency::query()->find($requestedAgencyId);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function syncProfile(User $user, array $validated, ?int $agencyId): void
    {
        if ($user->account_type === AccountType::Staff) {
            StaffProfile::query()->updateOrCreate(
                ['user_id' => $user->id, 'agency_id' => $agencyId],
                [
                    'job_title' => $validated['role_title'] ?? 'Staff',
                    'department' => $validated['department'] ?? 'Operations',
                    'is_active' => $user->status !== UserAccountStatus::Suspended,
                ]
            );
        }

        if ($user->account_type === AccountType::Agent) {
            Agent::query()->updateOrCreate(
                ['user_id' => $user->id, 'agency_id' => $agencyId],
                [
                    'code' => $validated['agent_code'] ?? ('AGT-'.strtoupper(str()->random(6))),
                    'commission_percent' => (float) ($validated['commission_percent'] ?? 0),
                    'is_active' => $user->status !== UserAccountStatus::Suspended,
                    'meta' => [
                        'agency_name' => $validated['agency_name'] ?? null,
                        'city' => $validated['city'] ?? null,
                        'phone' => $validated['phone'] ?? null,
                    ],
                ]
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request, bool $isEdit, ?User $target): array
    {
        $actor = $request->user();
        $requestedAccountType = (string) $request->input('account_type', '');

        // Enforce explicit authorization failure instead of a validation error.
        if (! $actor->isPlatformAdmin() && $requestedAccountType === AccountType::PlatformAdmin->value) {
            abort(403);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target?->id)],
            'account_type' => ['required', 'in:'.implode(',', $this->accountTypeOptions($actor))],
            'status' => ['required', Rule::enum(UserAccountStatus::class)],
            'agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:255'],
            'role_title' => ['nullable', 'string', 'max:255'],
            'permission_group' => ['nullable', 'string', 'max:255'],
            'agency_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'agent_code' => ['nullable', 'string', 'max:255'],
            'send_invite' => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules);
        if ($isEdit && $target !== null && ! $actor->isPlatformAdmin() && $target->current_agency_id !== $actor->current_agency_id) {
            abort(403);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    protected function writeAudit(User $actor, string $action, array $newValues): void
    {
        AuditLog::query()->create([
            'agency_id' => $actor->current_agency_id,
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => User::class,
            'auditable_id' => $newValues['user_id'] ?? $actor->id,
            'properties' => ['old_values' => [], 'new_values' => $newValues],
        ]);
    }
}
