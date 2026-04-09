<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\CRM\LeadSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-LC-001 — Public unauthenticated form submission validation
// BRD: CRM-CR-001 — consent_given MUST be accepted (required:accepted)
// BRD: CRM-CR-002 — consent_form_version stored for audit trail
class PublicFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public route — no auth required
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name'           => ['required', 'string', 'max:80'],
            'last_name'            => ['required', 'string', 'max:80'],
            'mobile'               => ['required', 'regex:/^[6-9]\d{9}$/'],
            'email'                => ['nullable', 'email:rfc', 'max:160'],
            'source'               => ['sometimes', new Enum(LeadSource::class)],
            'source_utm_params'    => ['nullable', 'array'],
            'source_utm_params.*'  => ['nullable', 'string', 'max:200'],
            // BRD: CRM-CR-001 — Consent is mandatory at point of public form submission
            'consent_given'        => ['required', 'accepted'],
            'consent_form_version' => ['required', 'string', 'max:30'],
            // Optional enrichment fields
            'city'                 => ['nullable', 'string', 'max:100'],
            'state'                => ['nullable', 'string', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:500'],
            'programme_interest'   => ['nullable', 'string', 'max:200'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mobile.regex'           => 'Please enter a valid 10-digit Indian mobile number.',
            'consent_given.accepted' => 'You must provide consent to proceed.',
        ];
    }
}
