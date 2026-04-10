<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\EmailProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-CC-004 — Sender domain registration validation
final class StoreSenderDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.settings.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'max:253',
                'regex:/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
                Rule::unique('sender_domains', 'domain')
                    ->where('institution_id', $this->user()->institution_id),
            ],
            'default_from_name'  => ['required', 'string', 'max:100'],
            'default_from_email' => ['required', 'email:rfc,dns', 'max:255'],
            'provider'           => ['required', Rule::enum(EmailProvider::class)],
            'is_default'         => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'domain.regex'  => 'Please enter a valid domain name (e.g. mail.example.com).',
            'domain.unique' => 'This domain is already registered for your institution.',
        ];
    }
}
