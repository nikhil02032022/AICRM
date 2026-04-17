<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\ErpConversion;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-016 — Trigger ERP conversion request validation
final class TriggerErpConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via Gate::authorize in controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:500',
        ];
    }
}
