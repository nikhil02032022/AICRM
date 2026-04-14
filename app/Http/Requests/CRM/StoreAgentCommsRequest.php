<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\AgentCommsChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-AG-008 — Validation for bulk agent communication broadcast
final class StoreAgentCommsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel'              => ['required', 'string', Rule::enum(AgentCommsChannel::class)],
            'subject'              => ['required_if:channel,email', 'nullable', 'string', 'max:255'],
            'message_body'         => ['required', 'string', 'min:10'],
            'recipient_agent_ids'  => ['required', 'array', 'min:1'],
            'recipient_agent_ids.*'=> ['integer', 'exists:users,id'],
        ];
    }
}
