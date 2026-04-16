<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-AP-010 — Validate bulk export action (web)
final class BulkApplicationExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.applications.view') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'application_uuids' => ['required', 'array', 'min:1'],
            'application_uuids.*' => ['required', 'string', 'exists:applications,uuid'],
            'format' => ['nullable', Rule::in(['csv', 'json'])],
        ];
    }
}
