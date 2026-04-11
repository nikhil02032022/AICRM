<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\AttributionModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-LC-017 — Validation for campaign spend entry creation.
final class StoreCampaignSpendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.campaigns.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source' => ['required', 'string', 'max:80'],
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'attribution_model' => ['nullable', Rule::enum(AttributionModel::class)],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
