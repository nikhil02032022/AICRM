<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-008 — API representation for programme-wise enrolment forecast snapshots
final class EnrolmentForecastResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'programme_id' => $this->crm_programme_id,
            'programme_name' => $this->programme?->name,
            'admission_cycle' => $this->admission_cycle,
            'forecast_count' => $this->forecast_count,
            'confidence_score' => $this->confidence_score,
            'inputs' => $this->inputs,
            'model_version' => $this->model_version,
            'generated_for_month' => $this->generated_for_month?->toDateString(),
            'generated_at' => $this->generated_at?->toIso8601String(),
        ];
    }
}
