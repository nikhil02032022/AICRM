<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\Enums\CRM\AttributionModel;
use App\Models\CRM\CampaignSpend;
use App\Models\CRM\Lead;
use App\Models\CRM\LeadAttribution;
use Illuminate\Database\Eloquent\Collection;

final class EloquentLeadAttributionRepository implements LeadAttributionRepositoryInterface
{
    public function create(array $data): LeadAttribution
    {
        return LeadAttribution::create($data);
    }

    public function listForLead(Lead $lead): Collection
    {
        return LeadAttribution::query()
            ->where('lead_id', $lead->id)
            ->orderBy('touchpoint_at')
            ->orderBy('id')
            ->get();
    }

    public function update(LeadAttribution $attribution, array $attributes): LeadAttribution
    {
        $attribution->update($attributes);

        return $attribution->refresh();
    }

    public function countAttributedLeadsForSpend(CampaignSpend $spend): int
    {
        $creditColumn = match ($spend->attribution_model) {
            AttributionModel::FIRST_TOUCH => 'first_touch_credit',
            AttributionModel::LAST_TOUCH => 'last_touch_credit',
            AttributionModel::LINEAR => 'linear_credit',
        };

        return LeadAttribution::query()
            ->whereDate('touchpoint_at', '>=', $spend->period_start)
            ->whereDate('touchpoint_at', '<=', $spend->period_end)
            ->where('source', $spend->source)
            ->when(
                $spend->campaign_name !== null && $spend->campaign_name !== '',
                fn ($query) => $query->where('utm_campaign', $spend->campaign_name),
            )
            ->where($creditColumn, '>', 0)
            ->distinct('lead_id')
            ->count('lead_id');
    }
}
