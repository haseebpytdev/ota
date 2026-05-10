<?php

namespace App\Http\Middleware;

use App\Enums\AccountType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerEmailVerifiedForPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || $user->account_type !== AccountType::Customer) {
            return $next($request);
        }

        if ($user->email_verified_at !== null) {
            return $next($request);
        }

        $deadline = $user->social_email_verification_deadline;
        if ($deadline === null || now()->lte($deadline)) {
            return $next($request);
        }

        return redirect()->route('verification.notice')->with('status', 'Please verify your email to continue accessing the customer portal.');
    }
}
