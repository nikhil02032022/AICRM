<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use App\Enums\CRM\IntegrationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-SA-010 — Validate integration credential creation form
final class StoreIntegrationCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.integrations.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'channel'   => ['required', Rule::enum(IntegrationChannel::class)],
            'label'     => ['required', 'string', 'max:200'],
            'is_active' => ['boolean'],

            // Credential fields — optional at creation, required per-channel (front-end enforces this)
            // Stored as JSON in the encrypted credentials column
            'credentials'                      => ['nullable', 'array'],
            'credentials.webhook_secret'       => ['nullable', 'string', 'max:500'],
            'credentials.app_secret'           => ['nullable', 'string', 'max:500'],
            'credentials.page_access_token'    => ['nullable', 'string', 'max:500'],
            'credentials.verify_token'         => ['nullable', 'string', 'max:500'],
            'credentials.page_id'              => ['nullable', 'string', 'max:100'],
            'credentials.form_id'              => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'channel.required' => 'Please select a channel for this integration.',
            'label.required'   => 'Please provide a label to identify this integration.',
        ];
    }
}
