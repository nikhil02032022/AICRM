<?php

declare(strict_types=1);

namespace App\Http\Middleware\CRM\Portal;

use App\Models\CRM\Institution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BrandingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $institution = $this->resolveInstitution($request);

        if ($institution !== null && ! $institution->is_active) {
            abort(403, 'This portal is currently unavailable.');
        }

        $request->attributes->set('portal_institution', $institution);
        view()->share('institution', $institution);
        view()->share('branding', $this->buildBranding($institution));

        return $next($request);
    }

    private function resolveInstitution(Request $request): ?Institution
    {
        // Development / testing bypass: ?institution={uuid}
        if ($request->filled('institution')) {
            return Institution::withoutGlobalScopes()
                ->where('uuid', $request->input('institution'))
                ->first();
        }

        return Institution::withoutGlobalScopes()
            ->where('domain', $request->getHost())
            ->first();
    }

    /**
     * @return array{name: string, logo_path: string, primary_color: string}
     */
    private function buildBranding(?Institution $institution): array
    {
        return [
            'name'          => $institution?->name
                                ?? config('crm_portal.branding.default_institution_name'),
            'logo_path'     => $institution?->logo_path
                                ?? config('crm_portal.branding.default_logo'),
            'primary_color' => data_get($institution?->settings, 'primary_color')
                                ?? config('crm_portal.branding.default_primary_color'),
        ];
    }
}
