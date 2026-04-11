<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\CampaignSpend;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CampaignSpend $resource
 */
final class CampaignSpendResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'source' => $this->resource->source,
            'campaign_name' => $this->resource->campaign_name,
            'period_start' => $this->resource->period_start?->toDateString(),
            'period_end' => $this->resource->period_end?->toDateString(),
            'amount' => (float) $this->resource->amount,
            'currency' => $this->resource->currency,
            'attribution_model' => $this->resource->attribution_model?->value,
            'attribution_model_label' => $this->resource->attribution_model?->label(),
            'attributed_leads_count' => $this->resource->getAttribute('attributed_leads_count'),
            'cost_per_lead' => $this->resource->getAttribute('cost_per_lead'),
            'notes' => $this->resource->notes,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
