<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\OfferLetterTemplate;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentOfferLetterTemplateRepository implements OfferLetterTemplateRepositoryInterface
{
    public function findByUuid(string $uuid): ?OfferLetterTemplate
    {
        return OfferLetterTemplate::query()
            ->where('uuid', $uuid)
            ->first();
    }

    public function findActiveByType(string $type): ?OfferLetterTemplate
    {
        return OfferLetterTemplate::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    public function paginateActive(int $perPage = 15): LengthAwarePaginator
    {
        return OfferLetterTemplate::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderByDesc('last_used_at')
            ->paginate($perPage);
    }

    public function create(array $data): OfferLetterTemplate
    {
        return OfferLetterTemplate::create($data);
    }

    public function update(OfferLetterTemplate $template, array $data): OfferLetterTemplate
    {
        $template->update($data);
        return $template->refresh();
    }

    public function delete(OfferLetterTemplate $template): bool
    {
        return (bool) $template->delete();
    }

    public function getOrCreateDefault(string $type): OfferLetterTemplate
    {
        $template = $this->findActiveByType($type);

        if ($template) {
            return $template;
        }

        // Create a basic default template
        return $this->create([
            'name' => match ($type) {
                'offer' => 'Default Offer Letter',
                'confirmation' => 'Default Confirmation Letter',
                default => "Default {$type} Letter",
            },
            'type' => $type,
            'is_active' => true,
            'html_template' => $this->getDefaultTemplate($type),
            'available_merge_tags' => OfferLetterTemplate::getDefaultMergeTags(),
        ]);
    }

    private function getDefaultTemplate(string $type): string
    {
        return match ($type) {
            'offer' => $this->getDefaultOfferTemplate(),
            'confirmation' => $this->getDefaultConfirmationTemplate(),
            default => '<html><body><p>Dear {{lead.first_name}},</p><p>Thank you for your application.</p></body></html>',
        };
    }

    private function getDefaultOfferTemplate(): string
    {
        return <<<'HTML'
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <style>
                    body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .content { margin: 30px 0; }
                    .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
                    .signature-block { margin-top: 50px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>{{institution.name}}</h1>
                    <p>OFFER OF ADMISSION</p>
                </div>

                <div class="content">
                    <p>Date: {{offer.generated_on}}</p>

                    <p>Dear {{lead.full_name}},</p>

                    <p>We are pleased to inform you that your application for admission to the <strong>{{application.programme_name}}</strong> programme has been selected.</p>

                    <p><strong>Offer Details:</strong></p>
                    <ul>
                        <li>Offer ID: {{offer.offer_id}}</li>
                        <li>Programme: {{application.programme_name}}</li>
                        <li>Applied On: {{application.applied_on}}</li>
                        <li>Offer Expiry: {{offer.expires_on}}</li>
                    </ul>

                    <p>Please confirm your acceptance of this offer within the specified timeline by logging into the student portal or contacting our admissions office.</p>

                    <p><strong>Contact Details:</strong></p>
                    <ul>
                        <li>Email: {{institution.contact_email}}</li>
                        <li>Phone: {{institution.contact_phone}}</li>
                    </ul>

                    <p>Congratulations once again on your selection!</p>

                    <p>Best regards,<br/>
                    {{institution.principal_name}}<br/>
                    {{institution.name}}</p>
                </div>

                <div class="signature-block">
                    <p>Principal's Signature: _______________</p>
                    <p>Date: _______________</p>
                </div>

                <div class="footer">
                    <p><small>This is an automatically generated document. Digital signature verification is available on the institution portal.</small></p>
                </div>
            </body>
            </html>
        HTML;
    }

    private function getDefaultConfirmationTemplate(): string
    {
        return <<<'HTML'
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <style>
                    body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .content { margin: 30px 0; }
                    .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>{{institution.name}}</h1>
                    <p>ADMISSION CONFIRMATION</p>
                </div>

                <div class="content">
                    <p>Date: {{offer.generated_on}}</p>

                    <p>Dear {{lead.full_name}},</p>

                    <p>We are pleased to confirm your admission to {{application.programme_name}} at {{institution.name}}.</p>

                    <p>Your admission is now confirmed. Please proceed with fee payment and document submission as per the instructions provided.</p>

                    <p>Best regards,<br/>
                    {{institution.name}}</p>
                </div>

                <div class="footer">
                    <p><small>This is an automatically generated document.</small></p>
                </div>
            </body>
            </html>
        HTML;
    }
}
