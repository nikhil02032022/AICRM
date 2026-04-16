<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-004 — Validation for online application fee payment capture
class PayApplicationFormDraftFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.applications.edit') ?? false;
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
