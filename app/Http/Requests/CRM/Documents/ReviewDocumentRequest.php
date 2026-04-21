<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Documents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('document.review') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject', 'request_reupload'])],
            'reason'   => ['required_unless:decision,approve', 'nullable', 'string', 'max:500'],
            'comment'  => ['nullable', 'string', 'max:1000'],
        ];
    }
}
