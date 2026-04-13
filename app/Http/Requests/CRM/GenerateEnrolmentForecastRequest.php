<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AI-008 — Validation for monthly forecast generation trigger
final class GenerateEnrolmentForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('crm.leads.edit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'for_month' => ['nullable', 'date_format:Y-m'],
        ];
    }
}
