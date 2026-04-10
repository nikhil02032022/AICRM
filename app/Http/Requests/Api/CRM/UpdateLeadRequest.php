<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LostReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-LC-011 — Validation for manual lead updates
final class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.leads.edit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:80'],
            'last_name' => ['sometimes', 'string', 'max:80'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:160'],
            'source' => ['sometimes', Rule::enum(LeadSource::class)],
            'status' => ['sometimes', Rule::enum(LeadStatus::class)],
            'assigned_counsellor_id' => ['sometimes', 'nullable', 'integer'],
            'campus_id' => ['sometimes', 'nullable', 'integer'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'programme_ids' => ['sometimes', 'nullable', 'array', 'max:5'],
            'programme_ids.*' => ['integer', 'min:1'],

            // BRD: CRM-EC-001 — Academic background
            'qualification' => ['sometimes', 'nullable', 'string', 'max:80'],
            'marks_10th' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'board_10th' => ['sometimes', 'nullable', 'string', 'max:100'],
            'marks_12th' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'board_12th' => ['sometimes', 'nullable', 'string', 'max:100'],
            'graduation_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'graduation_university' => ['sometimes', 'nullable', 'string', 'max:150'],
            'preferred_intake' => ['sometimes', 'nullable', 'string', 'max:10', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],

            // BRD: CRM-EC-013 — Lost reason required when transitioning to LOST
            'lost_reason' => [
                Rule::requiredIf(fn () => $this->input('status') === LeadStatus::LOST->value),
                'nullable',
                Rule::enum(LostReason::class),
            ],
        ];
    }
}
