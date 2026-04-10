<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\DltMessageType;
use App\Enums\CRM\SmsGateway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-CC-008 — Validate DLT template creation
final class StoreDltTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.communication.templates.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sender_id'     => ['required', 'string', 'max:10'],
            'template_name' => ['required', 'string', 'max:100'],
            'template_body' => ['required', 'string'],
            'message_type'  => ['required', Rule::enum(DltMessageType::class)],
            'gateway'       => ['required', Rule::enum(SmsGateway::class)],
        ];
    }
}
