<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-DM-007 — Validation for initiating Aadhaar eKYC session
final class InitiateAadhaarKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_uuid' => ['required', 'string', 'uuid', 'exists:leads,uuid'],
            // BRD: CRM-DM-007 — DPDP: Aadhaar number is never stored — only used transiently
            // The Aadhaar number is passed as a masked_aadhaar for display; the actual number
            // is submitted to the API Setu endpoint directly and not persisted anywhere.
        ];
    }
}
