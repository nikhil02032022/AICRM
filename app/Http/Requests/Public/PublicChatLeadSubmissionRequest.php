<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LC-006 — Validation for public chat widget lead submission
class PublicChatLeadSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'max:120'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'max:15'],
            'email' => ['nullable', 'email:rfc', 'max:160'],
            'campus_id' => ['nullable', 'integer'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'transcript' => ['nullable', 'array', 'max:30'],
            'transcript.*.role' => ['required_with:transcript', 'string', 'in:user,assistant'],
            'transcript.*.content' => ['required_with:transcript', 'string', 'max:1000'],
            'source_utm_params' => ['nullable', 'array'],
            'source_utm_params.utm_source' => ['nullable', 'string', 'max:100'],
            'source_utm_params.utm_medium' => ['nullable', 'string', 'max:100'],
            'source_utm_params.utm_campaign' => ['nullable', 'string', 'max:200'],
            'source_utm_params.utm_term' => ['nullable', 'string', 'max:200'],
            'source_utm_params.utm_content' => ['nullable', 'string', 'max:200'],
            'consent_given' => ['required', 'accepted'],
            'consent_form_version' => ['required', 'string', 'max:30'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
