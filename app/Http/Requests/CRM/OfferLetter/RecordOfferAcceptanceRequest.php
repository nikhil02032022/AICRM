<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\OfferLetter;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-015 — Record offer acceptance validation
final class RecordOfferAcceptanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('accept', $this->route('offer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
