<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Documents;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('document.checklist.manage') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'programme_id'  => ['nullable', 'integer', 'exists:crm_programmes,id'],
            'name'          => ['required', 'string', 'max:120'],
            'is_active'     => ['sometimes', 'boolean'],
            'items'         => ['sometimes', 'array'],
            'items.*.code'         => ['required_with:items', 'string', 'max:80'],
            'items.*.label'        => ['required_with:items', 'string', 'max:200'],
            'items.*.is_mandatory' => ['sometimes', 'boolean'],
            'items.*.max_size_kb'  => ['nullable', 'integer', 'min:1'],
            'items.*.allowed_mime' => ['nullable', 'array'],
            'items.*.sort_order'   => ['nullable', 'integer'],
        ];
    }
}
