<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\CustomFieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-EC-005 — Validate custom field update (field_key and entity are immutable)
class UpdateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label'              => ['sometimes', 'required', 'string', 'max:150'],
            'type'               => ['sometimes', 'required', new Enum(CustomFieldType::class)],
            'options'            => ['nullable', 'array'],
            'options.*.value'    => ['required_with:options', 'string', 'max:100'],
            'options.*.label'    => ['required_with:options', 'string', 'max:150'],
            'is_required'        => ['boolean'],
            'is_visible_in_list' => ['boolean'],
            'is_active'          => ['boolean'],
            'sort_order'         => ['integer', 'min:0'],
        ];
    }
}
