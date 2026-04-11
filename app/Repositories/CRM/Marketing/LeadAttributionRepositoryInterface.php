<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\Models\CRM\CampaignSpend;
use App\Models\CRM\Lead;
use App\Models\CRM\LeadAttribution;
use Illuminate\Database\Eloquent\Collection;

interface LeadAttributionRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): LeadAttribution;

    /** @return Collection<int, LeadAttribution> */
    public function listForLead(Lead $lead): Collection;

    /** @param array<string, mixed> $attributes */
    public function update(LeadAttribution $attribution, array $attributes): LeadAttribution;

    public function countAttributedLeadsForSpend(CampaignSpend $spend): int;
}
