<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-DM-006 — Validation for initiating a DigiLocker document request
final class InitiateDigiLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_uuid'         => ['required', 'string', 'uuid', 'exists:leads,uuid'],
            'document_type'     => ['required', 'string', 'max:80'],
            'consent_record_id' => ['required', 'integer', 'exists:consent_records,id'],
        ];
    }
}
