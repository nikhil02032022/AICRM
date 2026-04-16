<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-010 — Validate bulk counsellor assignment (web)
final class BulkApplicationAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.applications.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'application_uuids' => ['required', 'array', 'min:1'],
            'application_uuids.*' => ['required', 'string', 'exists:applications,uuid'],
            'counsellor_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
