<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// NFR-SE-003 — Enforces MFA verification for admin/manager roles.
// Redirects to verify (TOTP input) or setup (first-time enrollment) instead of aborting,
// enabling the enrollment flow without locking users out.
class RequireMfa
{
    private const MFA_EXEMPT_ROUTES = ['crm.mfa.setup', 'crm.mfa.enable', 'crm.mfa.verify', 'crm.mfa.show-verify', 'logout'];

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

        // Exempt MFA-related routes to avoid redirect loops
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        // Admin/manager who has never set up MFA → send to setup
        if ($user->hasAnyRole(['institution-admin', 'admissions_manager', 'super-admin']) && ! $user->mfa_enabled) {
            return redirect()->route('crm.mfa.setup');
        }

        // MFA is enabled but not yet verified this session → send to verify
        if ($user->mfa_enabled && $request->session()->get('mfa_verified') !== true) {
            return redirect()->route('crm.mfa.show-verify');
        }

        return $next($request);
    }

    private function isExemptRoute(Request $request): bool
    {
        foreach (self::MFA_EXEMPT_ROUTES as $routeName) {
            if ($request->routeIs($routeName)) {
                return true;
            }
        }

        return false;
    }
}
