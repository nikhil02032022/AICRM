<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\CallMonitorMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-TC-005 — Validation for starting supervisor monitoring session
final class StoreCallMonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'call_log_uuid' => ['required', 'uuid', Rule::exists('call_logs', 'uuid')],
            'mode' => ['required', Rule::enum(CallMonitorMode::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
