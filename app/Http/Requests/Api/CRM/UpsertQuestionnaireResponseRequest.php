<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LQ-009 — Validation for questionnaire response submission
final class UpsertQuestionnaireResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('crm.questionnaires.respond');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'responses' => ['required', 'array', 'min:1'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
