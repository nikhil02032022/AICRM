<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\Documents;

use App\Enums\CRM\Documents\DocumentUploadChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadApplicationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('document.upload') ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $max = (int) config('crm_documents.storage.max_size_kb', 10240);

        return [
            'application_uuid'           => ['required', 'uuid', 'exists:applications,uuid'],
            'document_checklist_item_id' => ['required', 'integer', 'exists:document_checklist_items,id'],
            'channel'                    => ['sometimes', Rule::in(array_column(DocumentUploadChannel::cases(), 'value'))],
            'file'                       => ['required', 'file', 'max:'.$max],
        ];
    }
}
