<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Models\CRM\Lead;
use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LC-019 — Validates manual lead merge request (API/Sanctum-auth context)
final class MergeLeadsApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.leads.merge') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'secondary_uuid' => ['required', 'uuid', 'different:' . $this->route('lead')->uuid],
            'confirm' => ['required', 'boolean', 'accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'secondary_uuid.required' => 'The secondary lead UUID is required.',
            'secondary_uuid.uuid' => 'The secondary lead UUID must be a valid UUID.',
            'secondary_uuid.different' => 'A lead cannot be merged with itself.',
            'confirm.accepted' => 'You must pass confirm: true to acknowledge the irreversibility of a merge.',
        ];
    }

    protected function passedValidation(): void
    {
        $primaryLead = $this->route('lead');
        $secondaryUuid = $this->validated('secondary_uuid');

        $secondary = Lead::withoutGlobalScopes()
            ->where('uuid', $secondaryUuid)
            ->where('institution_id', $primaryLead->institution_id)
            ->whereNull('deleted_at')
            ->whereNull('merged_into_uuid')
            ->first();

        if ($secondary === null) {
            $this->validator->errors()->add(
                'secondary_uuid',
                'The secondary lead does not exist, has already been merged, or belongs to a different institution.'
            );
        }
    }
}
