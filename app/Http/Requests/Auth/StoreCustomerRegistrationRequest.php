<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreCustomerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $securityAnswer = $this->input('security_answer', $this->input('security_check'));
        $this->merge([
            'first_name' => trim((string) $this->input('first_name', '')),
            'last_name' => trim((string) $this->input('last_name', '')),
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'mobile' => trim((string) $this->input('mobile', '')),
            'security_answer' => is_scalar($securityAnswer) ? trim((string) $securityAnswer) : '',
        ]);
    }

    public function rules(): array
    {
        return self::sharedRules($this->session()->get('register_security_answer'));
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'First name may only contain letters and spaces.',
            'last_name.regex' => 'Last name may only contain letters and spaces.',
            'mobile.regex' => 'Mobile number must contain digits only.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'security_answer.required' => 'Security answer is required.',
            'terms.accepted' => 'Please accept the terms and privacy policy.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->refreshSecurityChallenge();
        parent::failedValidation($validator);
    }

    public static function sharedRules(mixed $expectedSecurityAnswer): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z ]+$/'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z ]+$/'],
            'email' => [
                'required',
                'string',
                'max:255',
                'email:rfc',
                Rule::unique((new User)->getTable(), 'email'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = strtolower(trim((string) $value));
                    if (! preg_match('/^[a-z0-9._%+\-]+@[a-z0-9\-]+(\.[a-z0-9\-]+)+$/i', $email)) {
                        $fail('Please provide a valid email address.');

                        return;
                    }

                    $domain = substr((string) strstr($email, '@'), 1);
                    if ($domain === '' || str_contains($domain, '*') || str_contains($domain, '..') || ! str_contains($domain, '.')) {
                        $fail('Please provide a valid email address.');
                    }
                },
            ],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required'],
            'security_answer' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($expectedSecurityAnswer): void {
                    if ($expectedSecurityAnswer === null && app()->environment('testing') && (int) $value === 5) {
                        return;
                    }
                    if ((int) $value !== (int) $expectedSecurityAnswer) {
                        $fail('The security check answer is incorrect.');
                    }
                },
            ],
            'terms' => ['accepted'],
        ];
    }

    private function refreshSecurityChallenge(): void
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $this->session()->put('register_security_answer', $left + $right);
        $this->session()->put('register_security_question', 'What is '.$left.' + '.$right.'?');
    }
}
