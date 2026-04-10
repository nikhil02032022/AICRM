<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\LeadSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-LC-001 — Validation for API web form creation (institution admin / admissions-manager)
class StoreWebFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.forms.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'sometimes', 'string', 'max:80',
                'regex:/^[a-z0-9\-]+$/',
                // Uniqueness per institution enforced in the service layer
            ],
            'fields' => ['nullable', 'array'],
            'fields.*.id' => ['required_with:fields', 'string', 'max:60'],
            'fields.*.type' => ['required_with:fields', 'string', Rule::in(['text', 'tel', 'email', 'select', 'textarea', 'checkbox', 'hidden'])],
            'fields.*.label' => ['required_with:fields', 'string', 'max:160'],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.options_raw' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'fields.*.options' => ['sometimes', 'array'],
            'fields.*.options.*' => ['string', 'max:200'],
            'fields.*.show_if' => ['nullable', 'array'],
            'fields.*.show_if.field' => ['sometimes', 'string'],
            'fields.*.show_if.operator' => ['sometimes', Rule::in(['equals', 'not_equals', 'contains'])],
            'fields.*.show_if.value' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'source' => ['sometimes', new Enum(LeadSource::class)],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'consent_form_version' => ['required', 'string', 'max:30'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ];
    }
}
