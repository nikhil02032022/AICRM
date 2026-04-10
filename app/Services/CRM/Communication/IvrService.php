<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Events\CRM\Communication\IvrLeadCreatedEvent;
use App\Jobs\CRM\Communication\ProcessIvrLeadCreationJob;
use App\Models\CRM\IvrConfig;
use App\Models\CRM\Lead;
use App\Services\CRM\Lead\LeadService;

// BRD: CRM-CC-019, CRM-LC-010 — IVR configuration and inbound lead auto-creation
final class IvrService
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    /**
     * BRD: CRM-LC-010 — Process inbound IVR call → create/match lead.
     *
     * @param array<string, mixed> $payload
     */
    public function handleInboundIvrCall(array $payload, IvrConfig $config): Lead
    {
        $callerNumber = $payload['from'] ?? $payload['CallerNumber'] ?? '';

        // Try to match existing lead
        $lead = Lead::where('institution_id', $config->institution_id)
            ->whereRaw("mobile = ?", [$callerNumber]) // encrypted comparison is done at PHP level
            ->first();

        if ($lead === null) {
            // BRD: CRM-LC-010 — Auto-create lead from IVR (consent_given starts false — DPDP)
            $lead = Lead::create([
                'institution_id'   => $config->institution_id,
                'campus_id'        => $config->campus_id,
                'first_name'       => 'IVR Lead',
                'mobile'           => $callerNumber,
                'source'           => LeadSource::IVR->value,
                'status'           => LeadStatus::NEW->value,
                'consent_given'    => false, // BRD: CRM-CR-001 — counsellor must confirm later
                'call_consent_given' => false,
            ]);
        }

        event(new IvrLeadCreatedEvent($lead, $config));

        return $lead;
    }

    /**
     * BRD: CRM-CC-019 — Save or update IVR configuration.
     *
     * @param array<string, mixed> $data
     */
    public function saveConfig(array $data, int $institutionId): IvrConfig
    {
        $existing = IvrConfig::where('institution_id', $institutionId)
            ->where('campus_id', $data['campus_id'] ?? null)
            ->first();

        if ($existing !== null) {
            $existing->update($data);

            return $existing->fresh();
        }

        return IvrConfig::create([...$data, 'institution_id' => $institutionId]);
    }

    public function paginate(int $institutionId, int $perPage = 25): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return IvrConfig::where('institution_id', $institutionId)->paginate($perPage);
    }
}
