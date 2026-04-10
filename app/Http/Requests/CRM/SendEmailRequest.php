<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-CC-002 — Validate individual email send from lead record
final class SendEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.communication.send') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'template_id'      => ['nullable', 'integer', 'exists:communication_templates,id'],
            'from_name'        => ['required', 'string', 'max:100'],
            'from_email'       => ['required', 'email', 'max:255'],
            'subject'          => ['nullable', 'string', 'max:255'],
            'custom_body_html' => ['nullable', 'string'],
        ];
    }
}
