<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\CRM;

use App\Models\CRM\Lead;
use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-EC-007 — Validates the manual counsellor assignment form
final class StoreAssignLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Lead $lead */
        $lead = $this->route('lead');

        return $this->user()->can('assign', $lead);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'counsellor_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
