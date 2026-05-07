<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\User;
use App\Support\Auth\CheckoutReturnIntent;
use App\Support\Auth\LoginDestination;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        CheckoutReturnIntent::primeSessionFromQuery($request);

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'mobile' => ['required', 'string', 'max:50'],
            'security_check' => ['required', 'in:5'],
            'terms' => ['accepted'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => trim($request->first_name.' '.$request->last_name),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_type' => AccountType::Customer,
            'status' => UserAccountStatus::Active,
            'current_agency_id' => Agency::query()->where('slug', config('ota.default_agency_slug'))->value('id'),
            'meta' => [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->mobile,
            ],
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(LoginDestination::path($user));
    }
}
