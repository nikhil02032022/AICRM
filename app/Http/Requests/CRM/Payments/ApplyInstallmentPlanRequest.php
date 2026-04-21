<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Payments;

use Illuminate\Foundation\Http\FormRequest;

class ApplyInstallmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('installment.apply') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'application_uuid' => ['required', 'uuid', 'exists:applications,uuid'],
            'plan_id'          => ['required', 'integer', 'exists:fee_installment_plans,id'],
        ];
    }
}
