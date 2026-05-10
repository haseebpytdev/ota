<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreCustomerRegistrationRequest;
use App\Models\Agency;
use App\Models\User;
use App\Support\Auth\CheckoutReturnIntent;
use App\Support\Auth\LoginDestination;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        CheckoutReturnIntent::primeSessionFromQuery($request);

        $question = $this->storeSecurityChallenge($request);

        return view('auth.register', [
            'securityQuestion' => $question,
        ]);
    }

    public function store(StoreCustomerRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'account_type' => AccountType::Customer,
            'status' => UserAccountStatus::Active,
            'current_agency_id' => Agency::query()->where('slug', config('ota.default_agency_slug'))->value('id'),
            'meta' => [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['mobile'],
            ],
        ]);

        event(new Registered($user));

        Auth::login($user);
        $this->storeSecurityChallenge($request);

        return redirect()->intended(LoginDestination::path($user));
    }

    public function validateField(Request $request): JsonResponse
    {
        $field = trim((string) $request->input('field', ''));
        if ($field === '' || ! in_array($field, ['first_name', 'last_name', 'email', 'mobile', 'password', 'password_confirmation', 'security_answer'], true)) {
            return response()->json([
                'valid' => false,
                'errors' => ['field' => ['Invalid field for validation.']],
            ], 422);
        }

        $payload = [
            'first_name' => trim((string) $request->input('first_name', '')),
            'last_name' => trim((string) $request->input('last_name', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'mobile' => trim((string) $request->input('mobile', '')),
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
            'security_answer' => trim((string) $request->input('security_answer', $request->input('security_check', ''))),
            'terms' => '1',
        ];

        $rules = StoreCustomerRegistrationRequest::sharedRules($request->session()->get('register_security_answer'));
        $validator = Validator::make($payload, [
            $field => $rules[$field],
        ], (new StoreCustomerRegistrationRequest)->messages());

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'errors' => new \stdClass,
        ]);
    }

    private function storeSecurityChallenge(Request $request): string
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $question = 'What is '.$left.' + '.$right.'?';
        $request->session()->put('register_security_answer', $left + $right);
        $request->session()->put('register_security_question', $question);

        return $question;
    }
}
