<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use App\Enums\CRM\IntegrationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-LC-012 — Validate bulk CSV/Excel upload
// BRD: CRM-CR-001 — consent_attestation checkbox required (confirms all rows have consent)
final class BulkLeadImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.leads.import');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file'                => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'], // 5 MB
            'channel'             => ['required', Rule::enum(IntegrationChannel::class)],
            // BRD: CRM-CR-001 — Bulk import consent attestation. The person uploading
            // attests that explicit consent was obtained for all leads in this file.
            'consent_attestation' => ['required', 'accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.mimes'                    => 'The import file must be a CSV or Excel (.xlsx) file.',
            'file.max'                      => 'The import file must not exceed 5 MB.',
            'channel.required'              => 'Please select the source channel for this import.',
            'consent_attestation.accepted'  => 'You must confirm that explicit consent was obtained for all leads in this file.',
        ];
    }
}
