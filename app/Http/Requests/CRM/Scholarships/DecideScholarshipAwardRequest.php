<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Scholarships;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideScholarshipAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Fine-grained ability checks happen in the controller (stage-specific).
        return (bool) $this->user();
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'comment'  => ['nullable', 'string', 'max:1000'],
            'reason'   => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ];
    }
}
