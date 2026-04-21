<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ScholarshipType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScholarshipCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('scholarship.category.manage') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'programme_id'   => ['nullable', 'integer', 'exists:crm_programmes,id'],
            'campus_id'      => ['nullable', 'integer'],
            'code'           => ['required', 'string', 'max:40'],
            'name'           => ['required', 'string', 'max:120'],
            'type'           => ['required', Rule::in(array_column(ScholarshipType::cases(), 'value'))],
            'computation'    => ['required', Rule::in(['percent', 'flat'])],
            'value'          => ['required', 'numeric', 'min:0'],
            'max_cap'        => ['nullable', 'numeric', 'min:0'],
            'is_active'      => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
