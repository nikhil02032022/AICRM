<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\ReportFormat;
use App\Enums\CRM\ReportFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-AR-020 — Validate report schedule creation
class StoreReportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'custom_report_id' => ['required', 'integer', 'exists:custom_reports,id'],
            'name'             => ['required', 'string', 'max:200'],
            'frequency'        => ['required', new Enum(ReportFrequency::class)],
            'day_of_week'      => ['required_if:frequency,weekly', 'nullable', 'integer', 'min:0', 'max:6'],
            'day_of_month'     => ['required_if:frequency,monthly', 'nullable', 'integer', 'min:1', 'max:28'],
            'run_time'         => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'recipient_emails' => ['required', 'array', 'min:1', 'max:20'],
            'recipient_emails.*'=> ['required', 'email'],
            'format'           => ['required', new Enum(ReportFormat::class)],
            'is_active'        => ['boolean'],
        ];
    }
}
