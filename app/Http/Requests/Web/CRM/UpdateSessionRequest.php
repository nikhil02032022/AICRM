<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use App\Enums\CRM\CounsellingSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// BRD: CRM-EC-015 — Session outcome update validation
final class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.sessions.edit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(CounsellingSessionStatus::class),
            ],
            'post_session_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
