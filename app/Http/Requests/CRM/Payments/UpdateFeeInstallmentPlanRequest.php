<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeInstallmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('installment.plan.manage') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'programme_id'  => ['sometimes', 'nullable', 'integer', 'exists:crm_programmes,id'],
            'name'          => ['sometimes', 'string', 'max:120'],
            'fee_type'      => ['sometimes', Rule::in(array_column(FeeType::cases(), 'value'))],
            'total_amount'  => ['sometimes', 'numeric', 'min:0'],
            'schedule'      => ['sometimes', 'array', 'min:1'],
            'schedule.*.sequence'        => ['required_with:schedule', 'integer', 'min:1'],
            'schedule.*.percent'         => ['required_with:schedule', 'numeric', 'min:0', 'max:100'],
            'schedule.*.due_offset_days' => ['required_with:schedule', 'integer', 'min:0'],
            'is_active'                  => ['sometimes', 'boolean'],
        ];
    }
}
