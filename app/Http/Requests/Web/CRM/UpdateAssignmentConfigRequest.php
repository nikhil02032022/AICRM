<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use App\Enums\CRM\AssignmentMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-EC-006 — Validates the assignment configuration form
final class UpdateAssignmentConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.settings.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'assignment_mode' => ['required', Rule::enum(AssignmentMode::class)],
            'max_leads_per_counsellor' => ['required', 'integer', 'min:1', 'max:500'],
            'escalation_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'escalation_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
