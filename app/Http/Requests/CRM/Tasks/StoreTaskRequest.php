<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-TF-001 — Validation for task creation
final class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.tasks.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lead_id'     => ['required', 'integer', 'exists:leads,id'],
            'type'        => ['required', Rule::enum(TaskType::class)],
            'priority'    => ['required', Rule::enum(TaskPriority::class)],
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at'      => ['required', 'date', 'after:now'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
