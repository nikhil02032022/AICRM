<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LQ-007 — Validates a counsellor's manual score override submission
final class StoreScoreOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller via $this->authorize('override', $lead)
        return true;
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'override_score' => ['required', 'integer', 'min:0', 'max:100'],
            'reason'         => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.min' => 'Please provide a meaningful reason (at least 10 characters).',
        ];
    }
}
