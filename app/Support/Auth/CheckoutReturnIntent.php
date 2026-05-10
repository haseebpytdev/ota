<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;

/**
 * Stores Laravel's post-auth redirect when the user opens login/register from checkout.
 * Supports {@see redirect} (preferred) and legacy {@see checkout_return} query params.
 */
class CheckoutReturnIntent
{
    public static function primeSessionFromQuery(Request $request): void
    {
        $target = $request->query('redirect');
        if (! is_string($target) || $target === '') {
            $target = $request->query('checkout_return');
        }
        if (! is_string($target) || $target === '') {
            return;
        }

        if (! self::isAllowedCheckoutReturn($target)) {
            return;
        }

        $request->session()->put('url.intended', url($target));
    }

    public static function isAllowedCheckoutReturn(string $target): bool
    {
        if (str_contains($target, "\n") || str_contains($target, "\r")) {
            return false;
        }

        if (str_contains($target, '://')) {
            return false;
        }

        if (! str_starts_with($target, '/')) {
            return false;
        }

        if (str_starts_with($target, '//')) {
            return false;
        }

        $path = parse_url($target, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return false;
        }

        return str_starts_with($path, '/booking/passengers');
    }
}
