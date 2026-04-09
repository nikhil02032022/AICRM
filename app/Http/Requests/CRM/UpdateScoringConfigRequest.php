<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-LQ-001, CRM-LQ-005 — Validates the admin scoring configuration update form
final class UpdateScoringConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', \App\Models\CRM\InstitutionScoringConfig::class) ?? false;
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            // Signal weights — each 0–30, total does not need to equal 100 (service scales to 100 internally)
            'profile_completeness' => ['required', 'integer', 'min:0', 'max:30'],
            'programme_interest'   => ['required', 'integer', 'min:0', 'max:30'],
            'source_quality'       => ['required', 'integer', 'min:0', 'max:30'],
            'engagement'           => ['required', 'integer', 'min:0', 'max:30'],
            'consent'              => ['required', 'integer', 'min:0', 'max:10'],
            'geographic'           => ['required', 'integer', 'min:0', 'max:10'],
            'response_time'        => ['required', 'integer', 'min:0', 'max:10'],

            // BRD: CRM-LQ-005 — Temperature thresholds; hot must be strictly above warm
            'hot_threshold'  => ['required', 'integer', 'min:1', 'max:100', 'gt:warm_threshold'],
            'warm_threshold' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hot_threshold.gt' => 'The HOT threshold must be strictly greater than the WARM threshold.',
        ];
    }
}
