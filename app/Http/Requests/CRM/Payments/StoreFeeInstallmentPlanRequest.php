<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeeInstallmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('installment.plan.manage') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'programme_id'  => ['nullable', 'integer', 'exists:crm_programmes,id'],
            'name'          => ['required', 'string', 'max:120'],
            'fee_type'      => ['required', Rule::in(array_column(FeeType::cases(), 'value'))],
            'total_amount'  => ['required', 'numeric', 'min:0'],
            'schedule'      => ['required', 'array', 'min:1'],
            'schedule.*.sequence'        => ['required', 'integer', 'min:1'],
            'schedule.*.percent'         => ['required', 'numeric', 'min:0', 'max:100'],
            'schedule.*.due_offset_days' => ['required', 'integer', 'min:0'],
            'schedule.*.label'           => ['nullable', 'string', 'max:120'],
            'is_active'                  => ['sometimes', 'boolean'],
        ];
    }
}
