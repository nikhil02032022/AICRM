<?php

declare(strict_types=1);

namespace App\Services\CRM\Portal;

use App\Models\CRM\Application;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Portal\ErpBridgeToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;

// BRD: CRM-SP-007 — Issues single-use signed tokens for seamless ERP portal SSO on enrolment.
// When erp_bridge_base_url is empty the bridge is in stub mode (Sprint 4 mitigation — full
// ERP integration is completed in Sprint 5).
final class ErpBridgeService
{
    public function isEnabled(): bool
    {
        return filled(config('crm_portal.erp_bridge_base_url'));
    }

    /**
     * Issue a new single-use ERP bridge token for an enrolled applicant.
     *
     * @throws AuthorizationException if the application does not belong to the lead/institution
     * @throws \RuntimeException      if the application is not in the enrolled state
     */
    public function issue(Lead $lead, Application $application, Institution $institution): string
    {
        if ($application->lead_uuid !== $lead->uuid || $application->institution_id !== $institution->id) {
            throw new AuthorizationException('Application not found or access denied.');
        }

        if ($application->status->value !== 'enrolled') {
            throw new \RuntimeException('ERP bridge token can only be issued for enrolled applications.');
        }

        $plainToken = bin2hex(random_bytes(40));
        $expiryMinutes = (int) config('crm_portal.erp_bridge_token_expiry_minutes', 5);

        ErpBridgeToken::create([
            'lead_uuid'        => $lead->uuid,
            'institution_id'   => $institution->id,
            'application_uuid' => $application->uuid,
            'token_hash'       => hash('sha256', $plainToken),
            'issued_at'        => Carbon::now(),
            'expires_at'       => Carbon::now()->addMinutes($expiryMinutes),
        ]);

        return $plainToken;
    }

    /**
     * Build the ERP redirect URL containing the plain token.
     * The ERP portal is expected to call back to validate+consume the token.
     */
    public function buildRedirectUrl(string $plainToken, Lead $lead, Institution $institution): string
    {
        $base = rtrim((string) config('crm_portal.erp_bridge_base_url'), '/');

        return $base . '/sso?' . http_build_query([
            'token'       => $plainToken,
            'institution' => $institution->uuid,
            'applicant'   => $lead->uuid,
        ]);
    }

    /**
     * Validate and consume an ERP bridge token (called by the ERP portal).
     *
     * Returns the token record on success so the ERP can read lead_uuid / application_uuid.
     *
     * @throws \RuntimeException if the token is unknown, already used, or expired
     */
    public function consume(string $plainToken, Institution $institution): ErpBridgeToken
    {
        $record = ErpBridgeToken::where('token_hash', hash('sha256', $plainToken))
            ->where('institution_id', $institution->id)
            ->first();

        if ($record === null) {
            throw new \RuntimeException('Invalid ERP bridge token.');
        }

        if ($record->isUsed()) {
            throw new \RuntimeException('ERP bridge token has already been used.');
        }

        if ($record->isExpired()) {
            throw new \RuntimeException('ERP bridge token has expired.');
        }

        $record->markUsed();

        return $record;
    }
}
