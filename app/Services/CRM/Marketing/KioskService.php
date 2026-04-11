<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\DTOs\CRM\CreateKioskLeadDTO;
use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;
use App\Events\CRM\KioskLeadCreatedEvent;
use App\Models\CRM\Lead;
use App\Services\CRM\Lead\LeadService;
use App\Services\CRM\WebForm\PublicFormActor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-013 — Walk-in enquiry kiosk orchestration service
final class KioskService
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function captureLead(CreateKioskLeadDTO $dto, int $institutionId, string $ip): Lead
    {
        $utm = [
            'utm_source' => 'kiosk',
            'utm_medium' => 'walk_in',
        ];

        if ($dto->kioskLabel !== null && $dto->kioskLabel !== '') {
            $utm['utm_campaign'] = $dto->kioskLabel;
        }

        $lead = $this->leadService->create(
            new CreateLeadDTO(
                firstName: $dto->firstName,
                lastName: $dto->lastName,
                mobile: $dto->mobile,
                email: $dto->email,
                source: LeadSource::WALK_IN->value,
                consentGiven: $dto->consentGiven,
                consentIp: $ip,
                consentFormVersion: $dto->consentFormVersion,
                campusId: $dto->campusId,
                city: null,
                state: null,
                notes: 'Kiosk enquiry: '.mb_substr(trim($dto->queryMessage), 0, 280),
                sourceUtmParams: $utm,
                programmeIds: null,
            ),
            new PublicFormActor($institutionId),
        );

        Log::info('Kiosk lead created', [
            'lead_uuid' => $lead->uuid,
            'institution_id' => $institutionId,
            'capture_channel' => 'kiosk',
        ]);

        KioskLeadCreatedEvent::dispatch($lead);

        return $lead;
    }

    public function recentLeads(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return Lead::query()
            ->where('institution_id', $institutionId)
            ->where('source', LeadSource::WALK_IN->value)
            ->latest('created_at')
            ->paginate($perPage);
    }
}