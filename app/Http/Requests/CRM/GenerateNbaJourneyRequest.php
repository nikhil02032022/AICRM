<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-AI-010 — Validation for AI nurture journey suggestion generation trigger
final class GenerateNbaJourneyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('crm.leads.edit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'for_date' => ['nullable', 'date_format:Y-m-d'],
            'segment' => ['nullable', 'string', Rule::in(['hot_leads', 'warm_leads', 'cold_or_inactive', 'application_started'])],
        ];
    }
}
