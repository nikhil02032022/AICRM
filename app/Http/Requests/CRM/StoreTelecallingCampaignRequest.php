<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\TelecallingCampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-TC-006 — Validation for creating/updating telecalling campaigns
final class StoreTelecallingCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'campus_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(TelecallingCampaignStatus::class)],
            'start_time_window' => ['nullable', 'date'],
            'end_time_window' => ['nullable', 'date', 'after:start_time_window'],
            'agent_ids' => ['required', 'array', 'min:1'],
            'agent_ids.*' => ['required', 'integer', Rule::exists('users', 'id')],
            'lead_uuids' => ['required', 'array', 'min:1'],
            'lead_uuids.*' => ['required', 'uuid', Rule::exists('leads', 'uuid')],
        ];
    }
}
