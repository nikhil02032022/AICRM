<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateChatLeadHandoffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'handoff_status' => ['required', 'string', 'in:captured,pending_agent,live_agent,resolved'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
