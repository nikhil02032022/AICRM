<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\WorkflowNodeType;
use App\Enums\CRM\WorkflowStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-MA-001 — Validation for updating visual workflow definitions
class UpdateAutomationWorkflowRequest extends FormRequest
{
    /** @var list<string> */
    private const ALLOWED_TRIGGER_TYPES = [
        'lead_created',
        'form_submitted',
        'email_opened',
        'link_clicked',
        'lead_score_changed',
        'status_changed',
        'event_based',
        'date_time_based',
        'inactivity_timeout',
        're_engagement',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('crm.campaigns.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', new Enum(WorkflowStatus::class)],
            'trigger_type' => ['sometimes', Rule::in(self::ALLOWED_TRIGGER_TYPES)],
            'trigger_config' => ['nullable', 'array'],
            'steps_json' => ['nullable', 'string'],
            'steps' => ['sometimes', 'array', 'min:1', 'max:30'],
            'steps.*.id' => ['nullable', 'string', 'max:80'],
            'steps.*.order' => ['nullable', 'integer', 'min:0', 'max:500'],
            'steps.*.node_type' => ['required_with:steps', new Enum(WorkflowNodeType::class)],
            'steps.*.name' => ['required_with:steps', 'string', 'max:120'],
            'steps.*.config' => ['nullable', 'array'],
            'steps.*.delay_minutes' => ['nullable', 'integer', 'min:0', 'max:43200'],
            'campus_id' => ['nullable', 'integer', Rule::exists('campuses', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('steps_json')) {
            return;
        }

        $decoded = json_decode((string) $this->input('steps_json'), true);

        if (! is_array($decoded)) {
            return;
        }

        $this->merge([
            'steps' => array_values(array_filter($decoded, static fn (mixed $step): bool => is_array($step))),
        ]);
    }
}
