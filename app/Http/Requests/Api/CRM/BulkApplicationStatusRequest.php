<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-AP-010 — Validate bulk application status updates (API)
final class BulkApplicationStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(array_map(static fn (ApplicationStatus $status): string => $status->value, ApplicationStatus::cases()))],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
