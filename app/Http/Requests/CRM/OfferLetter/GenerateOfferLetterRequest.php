<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM\OfferLetter;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-AP-012 — Generate offer letter validation
final class GenerateOfferLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\CRM\OfferLetter::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expires_at' => 'nullable|date|after:today',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'reason' => 'nullable|string|max:500',
            // AP-014: Conditional offer fields
            'conditional' => 'nullable|boolean',
            'required_documents' => 'nullable|array',
            'required_documents.*' => 'string|max:50',
        ];
    }

    public function isConditional(): bool
    {
        return (bool) $this->input('conditional', false);
    }

    /**
     * @return array<string>
     */
    public function getRequiredDocuments(): array
    {
        return $this->input('required_documents', []);
    }

    public function getExpiryDate(): ?\DateTime
    {
        if ($this->has('expires_at')) {
            return new \DateTime($this->input('expires_at'));
        }

        if ($this->has('expires_in_days')) {
            return now()->addDays((int) $this->input('expires_in_days'))->toDateTime();
        }

        // Default: 30 days from now
        return now()->addDays(30)->toDateTime();
    }
}
