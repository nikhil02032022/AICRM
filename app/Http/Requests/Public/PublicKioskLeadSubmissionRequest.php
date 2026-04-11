<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LC-013 — Validation for public walk-in kiosk lead capture
// BRD: CRM-CR-001 — Consent is mandatory at point of capture
final class PublicKioskLeadSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'max:15'],
            'email' => ['nullable', 'email:rfc', 'max:160'],
            'campus_id' => ['nullable', 'integer'],
            'query_message' => ['required', 'string', 'max:1000'],
            'kiosk_label' => ['nullable', 'string', 'max:100'],
            'consent_given' => ['required', 'accepted'],
            'consent_form_version' => ['required', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mobile.regex' => 'Please enter a valid 10-digit Indian mobile number starting with 6-9.',
            'consent_given.accepted' => 'You must provide consent to proceed.',
        ];
    }
}