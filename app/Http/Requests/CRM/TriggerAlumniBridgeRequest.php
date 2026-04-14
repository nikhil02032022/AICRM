<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-EI-008 — Validation for triggering alumni bridge handoff
final class TriggerAlumniBridgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erp_student_id' => ['required', 'string', 'max:80'],
            'lead_uuid'      => ['required', 'string', 'uuid', 'exists:leads,uuid'],
        ];
    }
}
