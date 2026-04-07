<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureInstitutionTenancy — Guards all CRM routes.
 *
 * Aborts with 403 if the authenticated user has no institution_id,
 * preventing any tenant-unscoped access to CRM data.
 */
class EnsureInstitutionTenancy
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->institution_id === null) {
            abort(403, 'Access denied: no institution context.');
        }

        return $next($request);
    }
}
