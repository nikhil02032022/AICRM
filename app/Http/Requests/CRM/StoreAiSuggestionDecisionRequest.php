<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-AI-011 — Validation for human decision actions over AI suggestions
final class StoreAiSuggestionDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('crm.leads.edit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lead_uuid' => ['nullable', 'uuid'],
            'suggestion_type' => ['required', 'string', Rule::in([
                'next_best_action',
                'message_draft',
                'sentiment_flag',
                'churn_flag',
                'priority_lead',
                'forecast',
                'anomaly_alert',
                'nurture_journey',
            ])],
            'suggestion_uuid' => ['nullable', 'uuid'],
            'decision' => ['required', 'string', Rule::in(['accepted', 'edited', 'dismissed'])],
            'edited_content' => ['nullable', 'string', 'max:12000', 'required_if:decision,edited'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
