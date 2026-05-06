<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AgentApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
