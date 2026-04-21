<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Tasks;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-TF-008 — Validation for bulk task assignment
final class BulkAssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.tasks.bulk-assign');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'task_uuids'   => ['required', 'array', 'min:1', 'max:100'],
            'task_uuids.*' => ['uuid', 'exists:crm_tasks,uuid'],
            'assigned_to'  => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
