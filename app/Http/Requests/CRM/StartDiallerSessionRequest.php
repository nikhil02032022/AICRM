<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-TC-001 — Validation for starting an auto-dialler queue session
final class StartDiallerSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'lead_limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'lead_uuids' => ['nullable', 'array', 'max:200'],
            'lead_uuids.*' => ['uuid', 'exists:leads,uuid'],
        ];
    }
}
