<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-TC-002 — Validation for runtime call script branch resolution
final class ResolveCallScriptBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_step_key' => ['required', 'string', 'max:80'],
            'response' => ['nullable'],
        ];
    }
}
