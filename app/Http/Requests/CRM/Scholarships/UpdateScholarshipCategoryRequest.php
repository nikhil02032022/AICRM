<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ScholarshipType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScholarshipCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('scholarship.category.manage') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'programme_id'   => ['sometimes', 'nullable', 'integer', 'exists:crm_programmes,id'],
            'name'           => ['sometimes', 'string', 'max:120'],
            'type'           => ['sometimes', Rule::in(array_column(ScholarshipType::cases(), 'value'))],
            'computation'    => ['sometimes', Rule::in(['percent', 'flat'])],
            'value'          => ['sometimes', 'numeric', 'min:0'],
            'max_cap'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_active'      => ['sometimes', 'boolean'],
            'effective_from' => ['sometimes', 'nullable', 'date'],
            'effective_to'   => ['sometimes', 'nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
