<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureInstitutionTenancy;
use App\Http\Middleware\RequireMfa;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenancy' => EnsureInstitutionTenancy::class,
            'mfa' => RequireMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A2A-CRM API: all exceptions on /api/* routes return standard envelope
        // OWASP A06: never expose stack traces or internal error details to API consumers
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                if ($e instanceof AuthenticationException) {
                    return response()->json(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Unauthenticated.']], 401);
                }

                if ($e instanceof AuthorizationException) {
                    return response()->json(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => $e->getMessage() ?: 'Forbidden.']], 403);
                }

                if ($e instanceof ModelNotFoundException) {
                    return response()->json(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Resource not found.']], 404);
                }

                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'The given data was invalid.'],
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Generic: never expose internal message in production
                $message = app()->isProduction() ? 'An unexpected error occurred.' : $e->getMessage();

                return response()->json(['success' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => $message]], $status >= 400 ? $status : 500);
            }
        });
    })->create();
