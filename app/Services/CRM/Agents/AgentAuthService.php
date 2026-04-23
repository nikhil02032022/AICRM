<?php

declare(strict_types=1);

namespace App\Services\CRM\Agents;

use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// BRD: CRM-AG-003 — Email+password session auth for agent portal (mirrors PortalAuthService pattern)
final class AgentAuthService
{
    private const SESSION_LIFETIME_HOURS = 8;

    public function attemptLogin(string $email, string $password, int $institutionId, ?string $ipAddress = null, ?string $userAgent = null): ?AgentSession
    {
        /** @var Agent|null $agent */
        $agent = Agent::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('email', $email)
            ->where('status', 'active')
            ->first();

        if ($agent === null || ! Hash::check($password, $agent->password)) {
            return null;
        }

        $plain = Str::random(64);

        return AgentSession::create([
            'agent_id'   => $agent->id,
            'token_hash' => hash('sha256', $plain . $agent->id),
            'expires_at' => Carbon::now()->addHours(self::SESSION_LIFETIME_HOURS),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => Carbon::now(),
            // Store the plain token temporarily in a non-persisted attribute
            // so the controller can set the cookie
        ]);
    }

    /**
     * Issue a new agent session and return the plain token (cookie value).
     */
    public function issueSession(Agent $agent, ?string $ipAddress = null, ?string $userAgent = null): string
    {
        $plain = Str::random(64);

        AgentSession::create([
            'agent_id'   => $agent->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => Carbon::now()->addHours(self::SESSION_LIFETIME_HOURS),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => Carbon::now(),
        ]);

        return $plain;
    }

    /**
     * Resolve a plain cookie token to an active, unexpired AgentSession.
     */
    public function resolveSession(string $plain): ?AgentSession
    {
        return AgentSession::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where('expires_at', '>', Carbon::now())
            ->with('agent')
            ->first();
    }

    /**
     * Find agent by email+institution and check password, then issue session.
     * Returns [Agent, plainToken] on success, null on failure.
     *
     * @return array{Agent, string}|null
     */
    public function login(string $email, string $password, int $institutionId, ?string $ipAddress = null, ?string $userAgent = null): ?array
    {
        /** @var Agent|null $agent */
        $agent = Agent::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('email', $email)
            ->where('status', 'active')
            ->first();

        if ($agent === null || ! Hash::check($password, $agent->password)) {
            return null;
        }

        $plain = $this->issueSession($agent, $ipAddress, $userAgent);

        return [$agent, $plain];
    }

    public function logout(string $plain): void
    {
        AgentSession::query()
            ->where('token_hash', hash('sha256', $plain))
            ->delete();
    }
}
