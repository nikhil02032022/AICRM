<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-MA-010 — Validation for automation performance reporting filters
final class IndexAutomationPerformanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.campaigns.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'workflow_uuid' => ['sometimes', 'uuid', Rule::exists('automation_workflows', 'uuid')],
        ];
    }
}
