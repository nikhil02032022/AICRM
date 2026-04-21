<?php

declare(strict_types=1);

namespace App\Http\Middleware\CRM\Portal;

use App\Models\CRM\Institution;
use App\Services\CRM\Portal\PortalAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// BRD: CRM-SP-002 — Guards portal routes; validates portal session cookie and injects applicant context
final class PortalAuthenticate
{
    public function __construct(private readonly PortalAuthService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('portal_session');

        if (! $token) {
            return $this->unauthenticated($request);
        }

        $session = $this->authService->validateSession($token);

        if ($session === null || $session->isExpired()) {
            return $this->unauthenticated($request);
        }

        $institution = Institution::withoutGlobalScopes()->find($session->institution_id);

        // Make session + institution available to controllers and views
        $request->attributes->set('portal_session', $session);
        $request->attributes->set('portal_institution', $institution);
        view()->share('portalSession', $session);
        view()->share('institution', $institution);
        view()->share('branding', $this->buildBranding($institution));

        return $next($request);
    }

    /** @return array{name: string, logo_path: string, primary_color: string} */
    private function buildBranding(?Institution $institution): array
    {
        return [
            'name'          => $institution?->name          ?? config('crm_portal.branding.default_institution_name'),
            'logo_path'     => $institution?->logo_path     ?? config('crm_portal.branding.default_logo'),
            'primary_color' => data_get($institution?->settings, 'primary_color')
                               ?? config('crm_portal.branding.default_primary_color'),
        ];
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(
                ['success' => false, 'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Portal session required.']],
                401
            );
        }

        return redirect()->route('portal.auth.login')
            ->with('error', 'Your session has expired. Please log in again.');
    }
}
