<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use App\Enums\CRM\SessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-EC-015 — Internal session booking form validation
final class BookSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.sessions.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'counsellor_id' => ['required', 'integer', 'exists:users,id'],
            'session_type' => ['required', Rule::enum(SessionType::class)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'mode' => ['required', 'in:online,offline,phone'],
            'pre_session_notes' => ['nullable', 'string', 'max:2000'],
            'availability_slot_id' => ['nullable', 'string', 'exists:counsellor_availability_slots,id'],
        ];
    }
}
