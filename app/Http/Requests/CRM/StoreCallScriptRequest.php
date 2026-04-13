<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\CallScriptResponseType;
use App\Enums\CRM\CallScriptStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-TC-002 — Validation for creating/updating call scripts with branch rules
final class StoreCallScriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'status' => ['nullable', Rule::enum(CallScriptStatus::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'campus_id' => ['nullable', 'integer', 'min:1'],
            'is_default' => ['nullable', 'boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_key' => ['required', 'string', 'max:80'],
            'steps.*.step_order' => ['nullable', 'integer', 'min:1'],
            'steps.*.prompt_text' => ['required', 'string', 'max:2000'],
            'steps.*.response_type' => ['required', Rule::enum(CallScriptResponseType::class)],
            'steps.*.options' => ['nullable', 'array'],
            'steps.*.options.*' => ['nullable', 'string', 'max:200'],
            'steps.*.branch_rules' => ['nullable', 'array'],
            'steps.*.branch_rules.*.operator' => ['required_with:steps.*.branch_rules', 'string', Rule::in(['equals', 'contains', 'gte', 'lte', 'truthy'])],
            'steps.*.branch_rules.*.value' => ['nullable'],
            'steps.*.branch_rules.*.next_step_key' => ['required_with:steps.*.branch_rules', 'string', 'max:80'],
            'steps.*.default_next_step_key' => ['nullable', 'string', 'max:80'],
            'steps.*.is_terminal' => ['nullable', 'boolean'],
        ];
    }
}
