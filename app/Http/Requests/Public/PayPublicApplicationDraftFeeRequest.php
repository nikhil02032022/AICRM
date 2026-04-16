<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-004 — Validation for public online application fee payment action
class PayPublicApplicationDraftFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'transaction_reference' => ['sometimes', 'nullable', 'string', 'max:120'],
            'gateway' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
