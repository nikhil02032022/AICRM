<?php

declare(strict_types=1);

namespace App\Http\Middleware\CRM\AgentPortal;

use App\Services\CRM\Agents\AgentAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// BRD: CRM-AG-003 — Guards agent portal routes; validates agent session cookie
final class AgentAuthenticate
{
    public function __construct(private readonly AgentAuthService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('agent_portal_session');

        if (! $token) {
            return $this->unauthenticated($request);
        }

        $session = $this->authService->resolveSession($token);

        if ($session === null || $session->isExpired()) {
            return $this->unauthenticated($request);
        }

        $agent = $session->agent;

        if ($agent === null || ! $agent->isActive()) {
            return $this->unauthenticated($request);
        }

        // Make agent + session available to controllers and views
        $request->attributes->set('agent_session', $session);
        $request->attributes->set('agent', $agent);
        view()->share('agentSession', $session);
        view()->share('authAgent', $agent);

        return $next($request);
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(
                ['success' => false, 'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Agent session required.']],
                401
            );
        }

        return redirect()->route('agent-portal.login')
            ->with('error', 'Your session has expired. Please log in again.');
    }
}
