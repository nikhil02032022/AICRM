<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use App\Models\CRM\Lead;
use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LC-019 — Validates manual lead merge request (web/session-auth context)
final class MergeLeadsRequest extends FormRequest
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
            'confirm' => ['required', 'accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'secondary_uuid.required' => 'The secondary lead UUID is required.',
            'secondary_uuid.uuid' => 'The secondary lead UUID must be a valid UUID.',
            'secondary_uuid.different' => 'A lead cannot be merged with itself.',
            'confirm.accepted' => 'You must confirm the merge action before proceeding.',
        ];
    }

    /**
     * Validate that the secondary lead exists in the same institution and is not already merged.
     * Custom after-validation check to provide a clear error message.
     *
     * @return array<string, mixed>
     */
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
