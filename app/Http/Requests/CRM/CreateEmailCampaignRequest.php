<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-CC-002 — Validate email campaign creation
final class CreateEmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.campaigns.send') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'                              => ['required', 'string', 'max:100'],
            'subject'                           => ['required', 'string', 'max:255'],
            'template_id'                       => ['required', 'integer', 'exists:communication_templates,id'],
            'from_name'                         => ['required', 'string', 'max:100'],
            'from_email'                        => ['required', 'email', 'max:255'],
            'sender_domain_id'                  => ['nullable', 'integer', 'exists:sender_domains,id'],
            'recipient_filter'                  => ['nullable', 'array'],
            'recipient_filter.statuses'         => ['nullable', 'array'],
            'recipient_filter.statuses.*'       => ['string'],
            'recipient_filter.sources'          => ['nullable', 'array'],
            'recipient_filter.sources.*'        => ['string'],
            'recipient_filter.temperatures'     => ['nullable', 'array'],
            'recipient_filter.temperatures.*'   => ['string'],
            'recipient_filter.date_from'        => ['nullable', 'date'],
            'recipient_filter.date_to'          => ['nullable', 'date', 'after_or_equal:recipient_filter.date_from'],
            'scheduled_at'                      => ['nullable', 'date', 'after:now'],
            'campus_id'                         => ['nullable', 'integer'],
        ];
    }
}
