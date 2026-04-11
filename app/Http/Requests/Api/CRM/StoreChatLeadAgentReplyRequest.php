<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;

final class StoreChatLeadAgentReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000', 'regex:/\\S/'],
        ];
    }
}
