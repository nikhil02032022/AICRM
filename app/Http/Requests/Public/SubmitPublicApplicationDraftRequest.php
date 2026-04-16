<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-003 — Validation for public final submission of application draft
class SubmitPublicApplicationDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_section_id' => ['sometimes', 'nullable', 'string', 'max:60'],
            'last_completed_section_order' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'progress_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'form_data' => ['sometimes', 'array'],
            'programme_uuids' => ['sometimes', 'array', 'min:1', 'max:10'],
            'programme_uuids.*' => ['string', 'uuid'],
        ];
    }
}
