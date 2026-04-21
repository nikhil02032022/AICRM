<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Documents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('document.checklist.manage') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'programme_id' => ['sometimes', 'nullable', 'integer', 'exists:crm_programmes,id'],
            'name'         => ['sometimes', 'string', 'max:120'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }
}
