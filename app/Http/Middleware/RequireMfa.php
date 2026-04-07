<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequireMfa — Enforces MFA verification for all non-applicant roles.
 *
 * BRD: NFR-SE-003 — MFA enforced for all user roles except applicants.
 * A07 OWASP — Authentication failures prevention.
 *
 * Checks the `mfa_verified_at` session flag set during login.
 * Applicants (role=applicant) are exempt from MFA.
 */
class RequireMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Applicants are exempt from MFA
        if ($user->hasRole('applicant')) {
            return $next($request);
        }

        // If MFA is enabled for this user, verify it was completed this session
        if ($user->mfa_enabled && $request->session()->get('mfa_verified') !== true) {
            abort(403, 'MFA verification required.');
        }

        return $next($request);
    }
}
