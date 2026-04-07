<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\LeadSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-LC-011 — Validation for manual lead creation
// BRD: CRM-LC-014 — source is a required field on every lead creation request
// BRD: CRM-CR-001 — consent_given: accepted (true) is mandatory
final class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.leads.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name'           => ['required', 'string', 'max:80'],
            'last_name'            => ['required', 'string', 'max:80'],
            'mobile'               => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'max:15'],
            'email'                => ['nullable', 'email:rfc', 'max:160'],

            // BRD: CRM-LC-014 — mandatory source
            'source'               => ['required', Rule::enum(LeadSource::class)],
            'source_utm_params'    => ['nullable', 'array'],
            'source_utm_params.utm_source'   => ['nullable', 'string', 'max:100'],
            'source_utm_params.utm_medium'   => ['nullable', 'string', 'max:100'],
            'source_utm_params.utm_campaign' => ['nullable', 'string', 'max:200'],
            'source_utm_params.utm_term'     => ['nullable', 'string', 'max:200'],
            'source_utm_params.utm_content'  => ['nullable', 'string', 'max:200'],

            // Programme interest (optional at creation)
            'programme_ids'        => ['nullable', 'array', 'max:5'],
            'programme_ids.*'      => ['integer', 'min:1'],

            // DPDP: CRM-CR-001 — consent capture mandatory
            'consent_given'        => ['required', 'accepted'],
            'consent_form_version' => ['required', 'string', 'max:30'],

            'campus_id'            => ['nullable', 'integer'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'state'                => ['nullable', 'string', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mobile.regex'           => 'Please enter a valid 10-digit Indian mobile number starting with 6–9.',
            'source.required'        => 'The lead source is required.',
            'consent_given.accepted' => 'Applicant consent must be obtained before creating the lead.',
        ];
    }
}
