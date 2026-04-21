<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Documents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestBulkDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('document.bulk_download') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'scope'      => ['required', Rule::in(['application', 'programme_batch'])],
            'target_ref' => ['required', 'string', 'max:120'],
        ];
    }
}
