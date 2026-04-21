<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Enums\CRM\Payments\GatewayProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateFeeCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.collect') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'fee_type' => ['required', Rule::in(array_column(FeeType::cases(), 'value'))],
            'gateway'  => ['nullable', Rule::in(array_column(GatewayProvider::cases(), 'value'))],
        ];
    }
}
