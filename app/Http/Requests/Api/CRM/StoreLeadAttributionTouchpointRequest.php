<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LC-016 — Validation for adding a lead attribution touchpoint.
final class StoreLeadAttributionTouchpointRequest extends FormRequest
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
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'utm_term' => ['nullable', 'string', 'max:120'],
            'utm_content' => ['nullable', 'string', 'max:120'],
            'touchpoint_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
