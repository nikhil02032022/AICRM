<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fee_structure.manage') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'fee_type'       => ['sometimes', Rule::in(array_column(FeeType::cases(), 'value'))],
            'amount'         => ['sometimes', 'numeric', 'min:0'],
            'currency'       => ['sometimes', 'string', 'size:3'],
            'is_active'      => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
