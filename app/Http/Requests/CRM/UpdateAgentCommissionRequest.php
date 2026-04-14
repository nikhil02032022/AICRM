<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-AG-006 — Validation for approving or rejecting a commission
final class UpdateAgentCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'           => ['required', 'string', Rule::in(['approve', 'reject', 'pay'])],
            'approval_notes'   => ['nullable', 'string', 'max:500'],
            'payout_reference' => ['required_if:action,pay', 'nullable', 'string', 'max:100'],
        ];
    }
}
