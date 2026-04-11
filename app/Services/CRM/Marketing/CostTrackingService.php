<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\DTOs\CRM\CreateCampaignSpendDTO;
use App\Models\CRM\CampaignSpend;
use App\Repositories\CRM\Marketing\CampaignSpendRepositoryInterface;
use App\Repositories\CRM\Marketing\LeadAttributionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-LC-017 — Campaign spend persistence and CPL reporting.
final class CostTrackingService
{
    public function __construct(
        private readonly CampaignSpendRepositoryInterface $spendRepository,
        private readonly LeadAttributionRepositoryInterface $attributionRepository,
    ) {}

    public function createSpend(CreateCampaignSpendDTO $dto, int $institutionId, int $userId): CampaignSpend
    {
        return $this->spendRepository->create([
            'institution_id' => $institutionId,
            'campus_id' => $dto->campusId,
            'source' => $dto->source,
            'campaign_name' => $dto->campaignName,
            'period_start' => $dto->periodStart,
            'period_end' => $dto->periodEnd,
            'amount' => $dto->amount,
            'currency' => $dto->currency,
            'attribution_model' => $dto->attributionModel->value,
            'notes' => $dto->notes,
            'created_by' => $userId,
        ]);
    }

    /** @param array<string, mixed> $filters */
    public function report(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $spends = $this->spendRepository->paginate($filters, $perPage);

        $spends->getCollection()->transform(function (CampaignSpend $spend): CampaignSpend {
            $leadCount = $this->attributionRepository->countAttributedLeadsForSpend($spend);
            $amount = (float) $spend->amount;

            $spend->setAttribute('attributed_leads_count', $leadCount);
            $spend->setAttribute('cost_per_lead', $leadCount > 0 ? round($amount / $leadCount, 2) : null);

            return $spend;
        });

        return $spends;
    }
}
