<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\TemplateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-CC-001 — Validate communication template creation/update
final class StoreCommunicationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.communication.templates.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'channel'   => ['required', Rule::enum(CommunicationChannel::class)],
            'type'      => ['required', Rule::enum(TemplateType::class)],
            'subject'   => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $this->input('channel') === 'EMAIL')],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['required', 'string'],
            'merge_tags'=> ['nullable', 'array'],
            'campus_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
