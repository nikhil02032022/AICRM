<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LC-017 — Filter validation for campaign spend and CPL reporting lists.
final class IndexCostPerLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.campaigns.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source' => ['nullable', 'string', 'max:80'],
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
