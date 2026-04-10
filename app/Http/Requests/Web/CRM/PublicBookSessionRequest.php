<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use App\Enums\CRM\SessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-EC-016 — Public booking form validation (no auth — rate-limited at route level)
final class PublicBookSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route — no auth; DPDP consent enforced in controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'counsellor_id' => ['required', 'integer', 'exists:users,id'],
            'session_type' => ['required', Rule::enum(SessionType::class)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'mode' => ['required', 'in:online,offline,phone'],
            'pre_session_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
