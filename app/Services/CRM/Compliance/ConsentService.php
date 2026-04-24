<?php

declare(strict_types=1);

namespace App\Services\CRM\Compliance;

use App\Enums\CRM\Compliance\ConsentType;
use App\Enums\CRM\Compliance\OptOutChannel;
use App\Models\CRM\ConsentRecord;
use App\Models\CRM\Lead;
use Illuminate\Http\Request;

// BRD: CRM-CR-001 — Explicit consent at lead creation
// BRD: CRM-CR-002 — Consent records with timestamp, IP, form version
// BRD: CRM-CR-007 — Call recording consent notification
class ConsentService
{
    public function capture(
        Lead $lead,
        ConsentType $type,
        Request $request,
        string $formVersion = '1.0'
    ): ConsentRecord {
        return ConsentRecord::create([
            'lead_id'       => $lead->id,
            'institution_id' => $lead->institution_id,
            'consent_type'  => $type->value,
            'form_version'  => $formVersion,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'consented_at'  => now(),
        ]);
    }

    public function isConsentGiven(Lead $lead, ConsentType $type): bool
    {
        return ConsentRecord::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->where('consent_type', $type->value)
            ->whereNull('revoked_at')
            ->exists();
    }

    public function revokeForOptOut(Lead $lead, OptOutChannel $channel): void
    {
        ConsentRecord::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
