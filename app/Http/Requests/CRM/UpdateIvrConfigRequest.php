<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\TelephonyProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-CC-019 — IVR configuration update validation
final class UpdateIvrConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.settings.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'provider'              => ['sometimes', Rule::enum(TelephonyProvider::class)],
            'virtual_number'        => ['sometimes', 'string', 'max:20'],
            'welcome_message'       => ['sometimes', 'string', 'max:500'],
            'collect_name'          => ['boolean'],
            'collect_programme'     => ['boolean'],
            'fallback_counsellor_id'=> ['nullable', 'uuid', 'exists:users,id'],
            'is_active'             => ['boolean'],
        ];
    }
}
