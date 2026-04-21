<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Payments;

use App\Enums\CRM\Payments\PaymentChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.link.share') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'channel'   => ['required', Rule::in(array_column(PaymentChannel::cases(), 'value'))],
            'recipient' => ['required', 'string', 'max:191'],
        ];
    }
}
