<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class ConversionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'programme_id' => ['nullable', 'uuid'],
            'source' => ['nullable', 'string', 'max:64'],
            'counsellor_id' => ['nullable', 'uuid'],
            'batch' => ['nullable', 'string', 'max:64'],
        ];
    }
}
