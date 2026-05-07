<?php

namespace App\Http\Requests\Frontend;

use App\Models\User;
use App\Services\Booking\InternationalRouteDetector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreBookingPassengersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        $loggedIn = Auth::check();

        $emailRules = ['required', 'email', 'max:255'];
        if (! $loggedIn && $this->boolean('create_account')) {
            $emailRules[] = Rule::unique(User::class, 'email');
        }

        $passwordRules = ['nullable', 'confirmed'];
        if (! $loggedIn && $this->boolean('create_account')) {
            $passwordRules = ['required', 'confirmed', Password::defaults()];
        }

        return [
            'flight_id' => ['nullable', 'string', 'max:128'],
            'offer_id' => ['nullable', 'string', 'max:128'],
            'search_id' => ['nullable', 'string', 'max:128'],
            'title' => ['required', 'string', 'max:16'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'dob' => ['required', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'size:2'],
            'gender' => ['required', 'string', 'max:32'],
            'email' => $emailRules,
            'phone' => ['required', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'max:120'],
            'document_type' => ['nullable', 'string', 'in:passport,national_id'],
            'passport_number' => ['nullable', 'string', 'max:64'],
            'passport_issuing_country' => ['nullable', 'string', 'size:2'],
            'passport_expiry_date' => ['nullable', 'date', 'after:today'],
            'passport_issue_date' => ['nullable', 'date'],
            'national_id_number' => ['nullable', 'string', 'max:64'],
            'country_of_residence' => ['nullable', 'string', 'max:120'],
            'place_of_birth' => ['nullable', 'string', 'max:120'],
            'create_account' => ['sometimes', 'boolean'],
            'password' => $passwordRules,
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isMethod('post')) {
            return;
        }

        $this->merge([
            'create_account' => $this->boolean('create_account'),
            'document_type' => $this->input('document_type') ?: 'passport',
        ]);
    }

    public function withValidator($validator): void
    {
        if (! $this->isMethod('post')) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            $detector = app(InternationalRouteDetector::class);
            $intl = $detector->isInternational(
                (string) $this->input('from', ''),
                (string) $this->input('to', '')
            );

            $passportGate = (bool) config('ota.passport_required_for_international', true) && $intl;

            if ($passportGate) {
                foreach (['passport_number', 'passport_issuing_country', 'passport_expiry_date'] as $field) {
                    if (trim((string) $this->input($field)) === '') {
                        $validator->errors()->add($field, __('This field is required for international itineraries.'));
                    }
                }
            }

            $nationalIdGate = (bool) config('ota.require_domestic_national_id', false) && ! $intl;

            if ($nationalIdGate && trim((string) $this->input('national_id_number')) === '') {
                $validator->errors()->add('national_id_number', __('National ID is required for domestic itineraries.'));
            }
        });
    }
}
