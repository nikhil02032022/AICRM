<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fee_structure.manage') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'programme_id'   => ['required', 'integer', 'exists:crm_programmes,id'],
            'campus_id'      => ['nullable', 'integer'],
            'fee_type'       => ['required', Rule::in(array_column(FeeType::cases(), 'value'))],
            'amount'         => ['required', 'numeric', 'min:0'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'is_active'      => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
