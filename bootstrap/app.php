<?php

use App\Http\Middleware\EnsureAccountType;
use App\Http\Middleware\EnsureAgencyContext;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware([
                'web',
                'auth',
                'agency.context',
                'account.type:platform_admin,agency_admin',
            ])->group(base_path('routes/admin.php'));

            Route::middleware([
                'web',
                'auth',
                'agency.context',
                'account.type:platform_admin,agency_admin,staff',
            ])->group(base_path('routes/staff.php'));

            Route::middleware([
                'web',
                'auth',
                'agency.context',
                'account.type:agent',
            ])->group(base_path('routes/agent.php'));

            Route::middleware([
                'web',
                'auth',
                'agency.context',
                'account.type:customer',
            ])->group(base_path('routes/customer.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'agency.context' => EnsureAgencyContext::class,
            'account.type' => EnsureAccountType::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof AuthenticationException) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Authentication required.'], 401);
                }

                return redirect()->guest(route('login'));
            }

            if ($e instanceof AuthorizationException) {
                $message = app()->environment('production')
                    ? 'You do not have permission to access this area.'
                    : ($e->getMessage() !== '' ? $e->getMessage() : 'You do not have permission to access this area.');

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'You do not have permission to access this area.'], 403);
                }

                return response()->view('errors.403', ['message' => $message], 403);
            }

            if ($e instanceof AccessDeniedHttpException) {
                $message = app()->environment('production')
                    ? 'You do not have permission to access this area.'
                    : ($e->getMessage() !== '' ? $e->getMessage() : 'You do not have permission to access this area.');

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'You do not have permission to access this area.'], 403);
                }

                return response()->view('errors.403', ['message' => $message], 403);
            }

            if ($e instanceof TokenMismatchException) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Session expired. Please retry.'], 419);
                }

                return response()->view('errors.419', [], 419);
            }

            if ($e instanceof TooManyRequestsHttpException) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Too many requests. Please try again later.'], 429);
                }

                return response()->view('errors.429', [], 429);
            }

            if ($e instanceof QueryException) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Something went wrong on our side.'], 500);
                }

                return response()->view('errors.500', [], 500);
            }

            if ($request->expectsJson() && $e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => match ($e->getStatusCode()) {
                        401 => 'Authentication required.',
                        403 => 'You do not have permission to access this area.',
                        404 => 'The requested resource was not found.',
                        419 => 'Session expired. Please retry.',
                        429 => 'Too many requests. Please try again later.',
                        503 => 'Service is temporarily unavailable.',
                        default => 'Something went wrong on our side.',
                    },
                ], $e->getStatusCode());
            }

            if (! $request->expectsJson() && $e instanceof HttpExceptionInterface && $e->getStatusCode() === 403) {
                if (! app()->environment('production') && $e->getMessage() !== '') {
                    return response()->view('errors.403', ['message' => $e->getMessage()], 403);
                }

                return response()->view('errors.403', [], 403);
            }

            return null;
        });
    })->create();
