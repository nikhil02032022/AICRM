<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-003 — Validation for creating save-and-resume application drafts
class StoreApplicationFormDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.applications.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_section_id' => ['nullable', 'string', 'max:60'],
            'last_completed_section_order' => ['nullable', 'integer', 'min:1', 'max:100'],
            'progress_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'form_data' => ['sometimes', 'array'],
            'programme_uuids' => ['sometimes', 'array', 'min:1', 'max:10'],
            'programme_uuids.*' => ['string', 'uuid'],
            'expires_in_hours' => ['sometimes', 'integer', 'min:1', 'max:720'],
        ];
    }
}
