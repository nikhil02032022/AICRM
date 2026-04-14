<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\WorkflowTemplateCategory;
use App\Enums\CRM\WorkflowNodeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-SA-007 — Validate workflow template creation/update
class StoreWorkflowTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:200'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'category'      => ['required', new Enum(WorkflowTemplateCategory::class)],
            'trigger_type'  => ['required', 'string', 'max:50'],
            'template_data' => ['required', 'array'],
            'is_active'     => ['boolean'],
            'sort_order'    => ['integer', 'min:0'],
        ];
    }
}
