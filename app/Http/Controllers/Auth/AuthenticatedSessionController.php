<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Enums\OtaNotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Communication\OtaNotificationService;
use App\Support\Auth\CheckoutReturnIntent;
use App\Support\Auth\LoginDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected OtaNotificationService $notificationService,
    ) {}

    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        CheckoutReturnIntent::primeSessionFromQuery($request);

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->user()?->forceFill(['last_login_at' => now()])->save();
        $this->notifyLogin($request);

        $request->session()->regenerate();

        return redirect()->intended(LoginDestination::path($request->user()));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function notifyLogin(LoginRequest $request): void
    {
        $user = $request->user();
        if ($user === null || $user->currentAgency === null) {
            return;
        }

        if ($user->account_type === AccountType::Customer) {
            return;
        }

        $event = match ($user->account_type) {
            AccountType::AgencyAdmin, AccountType::PlatformAdmin => OtaNotificationEvent::AdminLoginSuccess,
            AccountType::Staff => OtaNotificationEvent::StaffLoginSuccess,
            AccountType::Agent => OtaNotificationEvent::AgentLoginSuccess,
            default => null,
        };

        if ($event === null) {
            return;
        }

        $this->notificationService->send(
            agency: $user->currentAgency,
            eventKey: $event->value,
            actor: $user,
            payload: [
                'account_type' => $user->account_type?->value,
                'timestamp' => now()->toIso8601String(),
                'ip' => (string) $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 250),
            ],
            fallbackSubject: 'Security login notice',
            fallbackBody: 'A privileged user login was detected for '.$user->email.'.',
            templateVariables: [
                'user_name' => $user->name,
                'account_type' => $user->account_type?->value,
                'ip' => (string) $request->ip(),
            ]
        );
    }
}
