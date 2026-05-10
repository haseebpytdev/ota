<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\AgentApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgentRegistrationController extends Controller
{
    public function landing(): View
    {
        return view('frontend.agent-registration.landing');
    }

    public function create(): View
    {
        return view('frontend.agent-registration.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['required', 'string', 'max:50'],
            'company_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'office_address' => ['required', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'cnic' => ['nullable', 'string', 'max:50'],
            'ntn' => ['nullable', 'string', 'max:50'],
            'iata_number' => ['nullable', 'string', 'max:50'],
            'years_in_business' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_booking_volume' => ['nullable', 'string', 'max:255'],
            'services_interested' => ['nullable', 'array'],
            'services_interested.*' => ['string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
        ]);

        $validated['email'] = Str::lower(trim((string) $validated['email']));

        $agentExists = User::query()
            ->whereRaw('LOWER(email) = ?', [$validated['email']])
            ->where('account_type', AccountType::Agent)
            ->exists();

        if ($agentExists) {
            return back()
                ->withErrors(['email' => 'An agent account already exists for this email address. Please sign in or contact support.'])
                ->withInput($request->except('terms'));
        }

        $existingApplication = AgentApplication::query()
            ->whereRaw('LOWER(email) = ?', [$validated['email']])
            ->first();

        if ($existingApplication) {
            return redirect()
                ->route('agent.register.submitted')
                ->with('status', 'We already received an agent application for this email address. Our team will review the existing application.');
        }

        AgentApplication::query()->create([
            ...$validated,
            'status' => 'pending',
        ]);

        return redirect()->route('agent.register.submitted');
    }

    public function submitted(): View
    {
        return view('frontend.agent-registration.submitted');
    }
}
