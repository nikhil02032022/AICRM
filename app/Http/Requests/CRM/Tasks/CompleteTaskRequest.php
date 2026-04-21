<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskDisposition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-TF-005 — Disposition is required on task completion
final class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'disposition' => ['required', Rule::enum(TaskDisposition::class)],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'disposition.required' => 'You must select an outcome/disposition to complete this task.',
        ];
    }
}
