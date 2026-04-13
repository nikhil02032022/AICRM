<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-AI-009 — Validation for anomaly detection trigger parameters
final class GenerateAnomalyDetectionRequest extends FormRequest
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
            'window_days' => ['nullable', 'integer', 'min:3', 'max:30'],
            'baseline_days' => ['nullable', 'integer', 'min:7', 'max:120'],
            'threshold_percent' => ['nullable', 'integer', Rule::in([15, 20, 25, 30, 40, 50])],
        ];
    }
}
