<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\LeadSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-LC-001 — Validation for API web form updates (partial update supported)
class UpdateWebFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.forms.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'                 => ['sometimes', 'string', 'max:120'],
            'slug'                 => [
                'sometimes', 'string', 'max:80',
                'regex:/^[a-z0-9\-]+$/',
            ],
            'fields'               => ['sometimes', 'array', 'min:1'],
            'fields.*.id'          => ['required_with:fields', 'string', 'max:60'],
            'fields.*.type'        => ['required_with:fields', 'string', Rule::in(['text','tel','email','select','textarea','checkbox','hidden'])],
            'fields.*.label'       => ['required_with:fields', 'string', 'max:160'],
            'fields.*.required'    => ['sometimes', 'boolean'],
            'fields.*.options_raw' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'fields.*.options'     => ['sometimes', 'array'],
            'fields.*.options.*'   => ['string', 'max:200'],
            'fields.*.show_if'     => ['nullable', 'array'],
            'is_active'            => ['sometimes', 'boolean'],
            'source'               => ['sometimes', new Enum(LeadSource::class)],
            'redirect_url'         => ['nullable', 'url', 'max:500'],
            'consent_form_version' => ['sometimes', 'string', 'max:30'],
            'accent_color'         => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_url'             => ['nullable', 'url', 'max:500'],
            'campus_id'            => ['nullable', 'integer', 'exists:campuses,id'],
            'custom_css'           => ['nullable', 'string', 'max:10000'],
        ];
    }
}
