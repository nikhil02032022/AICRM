<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-AP-010 — Validate bulk communication action (web)
final class BulkApplicationCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.communication.send') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'application_uuids' => ['required', 'array', 'min:1'],
            'application_uuids.*' => ['required', 'string', 'exists:applications,uuid'],
            'channel' => ['required', Rule::in(['EMAIL', 'SMS', 'WHATSAPP'])],
            'template_id' => ['nullable', 'integer'],
            'from_name' => ['required_if:channel,EMAIL', 'nullable', 'string', 'max:160'],
            'from_email' => ['required_if:channel,EMAIL', 'nullable', 'email:rfc', 'max:255'],
            'subject' => ['nullable', 'string', 'max:200'],
            'custom_body_html' => ['nullable', 'string', 'max:10000'],
            'dlt_template_id' => ['required_if:channel,SMS', 'nullable', 'integer', 'exists:dlt_templates,id'],
            'message' => ['nullable', 'string', 'max:1000'],
            'whatsapp_template_name' => ['required_if:channel,WHATSAPP', 'nullable', 'string', 'max:160'],
        ];
    }
}
