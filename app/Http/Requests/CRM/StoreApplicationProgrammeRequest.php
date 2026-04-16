<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-005 — Validation for institution programme catalogue setup
final class StoreApplicationProgrammeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.applications.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'level' => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'erp_programme_uuid' => ['nullable', 'uuid'],
        ];
    }
}
