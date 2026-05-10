<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\Auth\CheckoutReturnIntent;
use App\Support\Auth\LoginDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialAuthController extends Controller
{
    private const SUPPORTED_PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->assertSupportedProvider($provider);
        CheckoutReturnIntent::primeSessionFromQuery(request());

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->assertSupportedProvider($provider);

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors([
                'social' => 'Unable to sign in with '.ucfirst($provider).'. Please try again.',
            ]);
        }

        $user = $this->resolveLocalUser($provider, $oauthUser);

        if ($user === null) {
            return redirect()->route('login')->withErrors([
                'social' => 'Social sign in is available for customer accounts only. Please use your standard login method.',
            ]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->intended(LoginDestination::path($user));
    }

    private function assertSupportedProvider(string $provider): void
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            abort(404);
        }
    }

    private function resolveLocalUser(string $provider, SocialiteUser $oauthUser): ?User
    {
        $providerId = trim((string) $oauthUser->getId());
        if ($providerId === '') {
            return null;
        }

        $existingAccount = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
        if ($existingAccount !== null) {
            if ($existingAccount->user->account_type !== AccountType::Customer) {
                return null;
            }

            return $existingAccount->user;
        }

        $email = strtolower(trim((string) $oauthUser->getEmail()));
        $name = trim((string) ($oauthUser->getName() ?: $oauthUser->getNickname() ?: ''));
        $user = null;

        if ($email !== '') {
            $user = User::query()->where('email', $email)->first();
        }
        if ($user !== null && $user->account_type !== AccountType::Customer) {
            return null;
        }

        if ($user === null && $email !== '') {
            $user = User::query()->create([
                'name' => $name !== '' ? $name : 'Customer',
                'email' => $email,
                'password' => Str::password(40),
                'account_type' => AccountType::Customer,
                'status' => UserAccountStatus::Active,
                'current_agency_id' => Agency::query()->where('slug', config('ota.default_agency_slug'))->value('id'),
            ]);
            if ($this->isTrustedEmail($provider, $oauthUser)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            $user->forceFill([
                'social_email_verification_deadline' => now()->addDay(),
            ])->save();
        }

        if ($user === null) {
            return null;
        }

        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $providerId,
            'provider_email' => $email !== '' ? $email : null,
            'provider_name' => $name !== '' ? $name : null,
            'avatar' => $oauthUser->getAvatar(),
            'meta' => [
                'nickname' => $oauthUser->getNickname(),
            ],
        ]);

        return $user;
    }

    private function isTrustedEmail(string $provider, SocialiteUser $oauthUser): bool
    {
        if ($oauthUser->getEmail() === null) {
            return false;
        }

        if ($provider === 'google') {
            return true;
        }

        $raw = $oauthUser->user;
        if (! is_array($raw)) {
            return false;
        }

        return (bool) ($raw['email_verified'] ?? $raw['verified'] ?? false);
    }
}
