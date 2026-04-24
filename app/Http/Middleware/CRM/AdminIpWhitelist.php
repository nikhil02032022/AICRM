<?php

declare(strict_types=1);

namespace App\Http\Middleware\CRM;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// NFR-SE-005 — IP whitelist for admin-prefixed routes.
// Passes all requests when the whitelist is empty (opt-in behaviour).
// Blocks with 403 when the request IP is not in the configured list.
// Always exempts /health to allow load balancer probes regardless of IP config.
final class AdminIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        // Health endpoint must always be reachable by load balancer
        if ($request->is('health')) {
            return $next($request);
        }

        $whitelist = $this->resolveWhitelist($request);

        // Empty whitelist = feature not configured; allow all
        if (empty($whitelist)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if (! in_array($clientIp, $whitelist, true)) {
            abort(403, 'Access denied: your IP address is not whitelisted for admin access.');
        }

        return $next($request);
    }

    /** @return list<string> */
    private function resolveWhitelist(Request $request): array
    {
        try {
            $user = $request->user();
            if (! $user || ! $user->institution_id) {
                return [];
            }

            /** @var \App\Services\CRM\Admin\SystemConfigService $configService */
            $configService = app(\App\Services\CRM\Admin\SystemConfigService::class);
            $raw = $configService->get('admin_ip_whitelist', $user->institution_id, '');

            if (empty($raw)) {
                return [];
            }

            return array_values(array_filter(
                array_map('trim', explode("\n", (string) $raw)),
            ));
        } catch (\Throwable) {
            // Fail open: if config service is unavailable, allow all to avoid locking out admins
            return [];
        }
    }
}
