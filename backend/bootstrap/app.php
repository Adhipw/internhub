<?php

use App\Http\Middleware\AiConsentMiddleware;
use App\Http\Middleware\CheckBanned;
use App\Http\Middleware\CompanyScopeMiddleware;
use App\Http\Middleware\EnsureEmailIsVerifiedWithRoleBypass;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->statefulApi();

        $middleware->web(append: [
            RequestIdMiddleware::class,
            SetLocale::class,
            CheckBanned::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->api(append: [
            RequestIdMiddleware::class,
            CheckBanned::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/webhooks/*',
            'webhooks/integration/*',
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'company_scope' => CompanyScopeMiddleware::class,
            'verified' => EnsureEmailIsVerifiedWithRoleBypass::class,
            'ai_consent' => AiConsentMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            // Let Laravel handle these natively for proper API responses
            if ($e instanceof \Illuminate\Validation\ValidationException || 
                $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            $requestId = $request->header('X-Request-ID');
            $status = 500;
            if (method_exists($e, 'getStatusCode')) {
                $status = $e->getStatusCode();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
                    'request_id' => $requestId,
                    'status' => $status,
                ], $status);
            }

            if (! config('app.debug') && in_array($status, [403, 404, 500, 503])) {
                return Inertia::render('Error', [
                    'status' => $status,
                    'request_id' => $requestId,
                ])->toResponse($request)->setStatusCode($status);
            }
        });
    })->create();
