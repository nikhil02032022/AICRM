<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.refund.request') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
