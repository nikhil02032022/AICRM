<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use App\Enums\CRM\CallDisposition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-TC-003 — Validation for creating/updating call disposition configuration
final class StoreCallDispositionConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $codes = collect(CallDisposition::cases())->map(static fn (CallDisposition $d): string => $d->value)->all();

        return [
            'code' => [Rule::requiredIf($this->isMethod('post')), 'string', Rule::in($codes)],
            'label' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'requires_follow_up' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }
}
