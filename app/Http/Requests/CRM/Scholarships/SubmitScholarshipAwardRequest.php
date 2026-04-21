<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Scholarships;

use Illuminate\Foundation\Http\FormRequest;

class SubmitScholarshipAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('scholarship.award.submit') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'application_uuid'        => ['required', 'uuid', 'exists:applications,uuid'],
            'scholarship_category_id' => ['required', 'integer', 'exists:scholarship_categories,id'],
            'amount'                  => ['required', 'numeric', 'min:0'],
        ];
    }
}
