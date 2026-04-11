<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\LandingPageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-LC-005 — Validation for landing page partial updates
class UpdateLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.campaigns.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/'],
            'status' => ['sometimes', new Enum(LandingPageStatus::class)],
            'theme_variant' => ['sometimes', Rule::in(['scholar', 'sunrise', 'forest'])],
            'headline' => ['sometimes', 'string', 'max:180'],
            'subheadline' => ['nullable', 'string', 'max:320'],
            'hero_image_url' => ['nullable', 'url', 'max:500'],
            'cta_label' => ['sometimes', 'string', 'max:60'],
            'cta_secondary_label' => ['nullable', 'string', 'max:60'],
            'content_json' => ['nullable', 'string'],
            'content' => ['sometimes', 'array', 'max:6'],
            'content.*.id' => ['nullable', 'string', 'max:64'],
            'content.*.type' => ['nullable', Rule::in(['value_card', 'stat', 'faq'])],
            'content.*.order' => ['nullable', 'integer', 'min:0', 'max:99'],
            'content.*.eyebrow' => ['nullable', 'string', 'max:80'],
            'content.*.title' => ['nullable', 'string', 'max:120'],
            'content.*.body' => ['nullable', 'string', 'max:500'],
            'content.*.metric_label' => ['nullable', 'string', 'max:80'],
            'content.*.metric_value' => ['nullable', 'string', 'max:80'],
            'content.*.question' => ['nullable', 'string', 'max:180'],
            'content.*.answer' => ['nullable', 'string', 'max:600'],
            'attribution_params' => ['nullable', 'array'],
            'attribution_params.utm_source' => ['nullable', 'string', 'max:120'],
            'attribution_params.utm_medium' => ['nullable', 'string', 'max:120'],
            'attribution_params.utm_campaign' => ['nullable', 'string', 'max:120'],
            'attribution_params.utm_term' => ['nullable', 'string', 'max:120'],
            'attribution_params.utm_content' => ['nullable', 'string', 'max:120'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'web_form_id' => ['nullable', 'integer', 'exists:web_forms,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('content_json')) {
            return;
        }

        $decoded = json_decode((string) $this->input('content_json'), true);

        if (! is_array($decoded)) {
            return;
        }

        $this->merge([
            'content' => array_values(array_filter($decoded, static fn (mixed $block): bool => is_array($block))),
        ]);
    }
}