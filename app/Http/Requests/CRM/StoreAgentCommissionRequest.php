<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AG-006 — Validation for creating an agent commission record
final class StoreAgentCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_user_id'     => ['required', 'integer', 'exists:users,id'],
            'lead_uuid'         => ['required', 'string', 'uuid', 'exists:leads,uuid'],
            'commission_type'   => ['required', 'string', 'in:fixed,percentage'],
            'commission_amount' => ['required_if:commission_type,fixed', 'nullable', 'numeric', 'min:0'],
            'percentage_rate'   => ['required_if:commission_type,percentage', 'nullable', 'numeric', 'min:0', 'max:100'],
            'base_amount'       => ['required_if:commission_type,percentage', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
