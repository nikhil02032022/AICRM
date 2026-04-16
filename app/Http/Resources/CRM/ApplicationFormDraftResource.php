<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\ApplicationFormDraft;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ApplicationFormDraft $resource
 */
class ApplicationFormDraftResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'template_uuid' => $this->resource->template?->uuid,
            'resume_token' => $this->resource->resume_token,
            'status' => $this->resource->status?->value,
            'current_section_id' => $this->resource->current_section_id,
            'last_completed_section_order' => $this->resource->last_completed_section_order,
            'progress_percentage' => $this->resource->progress_percentage,
            'form_data' => $this->resource->form_data,
            'selected_programme_uuids' => $this->resource->selected_programme_uuids,
            'application_fee_amount' => $this->resource->application_fee_amount,
            'application_fee_currency' => $this->resource->application_fee_currency,
            'application_fee_status' => $this->resource->application_fee_status,
            'application_fee_transaction_reference' => $this->resource->application_fee_transaction_reference,
            'application_fee_gateway' => $this->resource->application_fee_gateway,
            'application_fee_paid_at' => $this->resource->application_fee_paid_at?->toIso8601String(),
            'last_saved_at' => $this->resource->last_saved_at?->toIso8601String(),
            'expires_at' => $this->resource->expires_at?->toIso8601String(),
            'submitted_at' => $this->resource->submitted_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
