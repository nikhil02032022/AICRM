<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-TF-001 — Validation for task update (all fields optional)
final class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type'        => ['sometimes', Rule::enum(TaskType::class)],
            'priority'    => ['sometimes', Rule::enum(TaskPriority::class)],
            'title'       => ['sometimes', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at'      => ['sometimes', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
