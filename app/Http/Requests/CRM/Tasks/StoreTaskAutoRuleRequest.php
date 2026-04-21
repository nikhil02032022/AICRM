<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-TF-002 — Validation for auto-rule creation (institution admin only)
final class StoreTaskAutoRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.task-auto-rules.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'campus_id'                   => ['nullable', 'integer', 'exists:campuses,id'],
            'trigger_type'                => ['required', 'in:inactivity'],
            'inactivity_threshold_hours'  => ['required', 'integer', 'min:1', 'max:720'],
            'task_type'                   => ['required', Rule::enum(TaskType::class)],
            'priority'                    => ['required', Rule::enum(TaskPriority::class)],
            'assignee_strategy'           => ['required', 'in:lead_owner,round_robin,least_loaded'],
        ];
    }
}
