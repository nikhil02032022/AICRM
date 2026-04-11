<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\Models\CRM\Lead;
use App\Models\CRM\LeadAttribution;
use App\Repositories\CRM\Marketing\LeadAttributionRepositoryInterface;

// BRD: CRM-LC-016 — Multi-touch attribution orchestration and credit recalculation.
final class AttributionService
{
    public function __construct(
        private readonly LeadAttributionRepositoryInterface $repository,
    ) {}

    public function recordInitialTouchpoint(Lead $lead): LeadAttribution
    {
        return $this->addTouchpoint(
            lead: $lead,
            payload: [
                'source' => $lead->source->value,
                'utm_source' => $lead->source_utm_params['utm_source'] ?? null,
                'utm_medium' => $lead->source_utm_params['utm_medium'] ?? null,
                'utm_campaign' => $lead->source_utm_params['utm_campaign'] ?? null,
                'utm_term' => $lead->source_utm_params['utm_term'] ?? null,
                'utm_content' => $lead->source_utm_params['utm_content'] ?? null,
                'touchpoint_at' => $lead->created_at ?? now(),
                'metadata' => ['capture' => 'lead_created'],
            ],
        );
    }

    /** @param array<string, mixed> $payload */
    public function addTouchpoint(Lead $lead, array $payload, ?int $createdBy = null): LeadAttribution
    {
        $attribution = $this->repository->create([
            'institution_id' => $lead->institution_id,
            'campus_id' => $lead->campus_id,
            'lead_id' => $lead->id,
            'touch_type' => 'middle_touch',
            'source' => (string) $payload['source'],
            'utm_source' => $payload['utm_source'] ?? null,
            'utm_medium' => $payload['utm_medium'] ?? null,
            'utm_campaign' => $payload['utm_campaign'] ?? null,
            'utm_term' => $payload['utm_term'] ?? null,
            'utm_content' => $payload['utm_content'] ?? null,
            'touchpoint_at' => $payload['touchpoint_at'] ?? now(),
            'metadata' => $payload['metadata'] ?? null,
            'created_by' => $createdBy,
        ]);

        $this->recalculateCredits($lead);

        return $attribution->refresh();
    }

    public function recalculateCredits(Lead $lead): void
    {
        $touchpoints = $this->repository->listForLead($lead)->values();
        $count = $touchpoints->count();

        if ($count === 0) {
            return;
        }

        $linearCredit = round(1 / $count, 4);

        foreach ($touchpoints as $index => $touchpoint) {
            $isFirst = $index === 0;
            $isLast = $index === ($count - 1);

            $touchType = $isFirst
                ? 'first_touch'
                : ($isLast ? 'last_touch' : 'middle_touch');

            $this->repository->update($touchpoint, [
                'touch_type' => $touchType,
                'is_first_touch' => $isFirst,
                'is_last_touch' => $isLast,
                'first_touch_credit' => $isFirst ? 1 : 0,
                'last_touch_credit' => $isLast ? 1 : 0,
                'linear_credit' => $linearCredit,
            ]);
        }
    }

    /** @return array<int, LeadAttribution> */
    public function timelineForLead(Lead $lead): array
    {
        return $this->repository->listForLead($lead)->all();
    }
}
