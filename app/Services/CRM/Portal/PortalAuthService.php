<?php

declare(strict_types=1);

namespace App\Services\CRM\Portal;

use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Portal\PortalSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

// BRD: CRM-SP-002 — Issue, validate, and revoke portal session tokens for authenticated applicants
final class PortalAuthService
{
    public function issueSession(
        Lead $lead,
        Institution $institution,
        ?string $deviceFingerprint = null,
    ): string {
        $plain = Str::random(64);

        PortalSession::create([
            'lead_uuid'          => $lead->uuid,
            'institution_id'     => $institution->id,
            'session_token_hash' => hash('sha256', $plain),
            'expires_at'         => Carbon::now()->addHours(
                config('crm_portal.session_lifetime_hours', 8)
            ),
            'device_fingerprint' => $deviceFingerprint,
        ]);

        return $plain;
    }

    public function validateSession(string $plain): ?PortalSession
    {
        /** @var PortalSession|null $session */
        $session = PortalSession::query()
            ->where('session_token_hash', hash('sha256', $plain))
            ->where('expires_at', '>', Carbon::now())
            ->first();

        return $session;
    }

    public function revokeSession(string $plain): void
    {
        PortalSession::query()
            ->where('session_token_hash', hash('sha256', $plain))
            ->delete();
    }
}
