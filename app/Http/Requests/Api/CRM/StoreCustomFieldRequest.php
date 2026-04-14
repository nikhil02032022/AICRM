<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\CustomFieldEntity;
use App\Enums\CRM\CustomFieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-EC-005 — Validate new custom field creation
class StoreCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC enforced via Gate in controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entity'             => ['required', new Enum(CustomFieldEntity::class)],
            'label'              => ['required', 'string', 'max:150'],
            'field_key'          => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'type'               => ['required', new Enum(CustomFieldType::class)],
            'options'            => ['nullable', 'array'],
            'options.*.value'    => ['required_with:options', 'string', 'max:100'],
            'options.*.label'    => ['required_with:options', 'string', 'max:150'],
            'is_required'        => ['boolean'],
            'is_visible_in_list' => ['boolean'],
            'sort_order'         => ['integer', 'min:0'],
            'campus_id'          => ['nullable', 'integer'],
        ];
    }
}
