<?php

namespace App\Http\Controllers;

use App\Support\Auth\LoginDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->to(LoginDestination::path($request->user()));
    }
}
