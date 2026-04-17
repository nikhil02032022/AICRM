<?php

declare(strict_types=1);

namespace App\Services\CRM\Application;

use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\OfferLetter;
use App\Models\CRM\OfferLetterTemplate;
use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;

// BRD: CRM-AP-012 — Render offer letter templates to PDF with merge tag substitution
final class OfferLetterRenderService
{
    /**
     * Render a template with merge tags replaced by actual data.
     *
     * @throws Html2PdfException
     */
    public function renderToPdf(
        OfferLetterTemplate $template,
        Lead $lead,
        Application $application,
        OfferLetter $offerLetter,
        ?array $customData = null,
    ): string {
        $html = $this->renderTemplate($template, $lead, $application, $offerLetter, $customData);
        return $this->convertHtmlToPdf($html);
    }

    /**
     * Render template with merge tags replaced by data (returns HTML).
     */
    public function renderTemplate(
        OfferLetterTemplate $template,
        Lead $lead,
        Application $application,
        OfferLetter $offerLetter,
        ?array $customData = null,
    ): string {
        $mergeData = $this->buildMergeData($lead, $application, $offerLetter, $customData);
        return $this->replaceMergeTags($template->html_template, $mergeData);
    }

    /**
     * Convert HTML string to PDF binary content.
     *
     * @throws Html2PdfException
     */
    private function convertHtmlToPdf(string $html): string
    {
        $pdf = new Html2Pdf('P', 'A4', 'en', true, 'UTF-8', [10, 10, 10, 10]);

        // Configure PDF metadata
        $pdf->setDefaultFont('Arial');
        $pdf->setFont('Arial');

        // Write HTML to PDF
        $pdf->writeHTML($html);

        // Return PDF as binary string
        return $pdf->output('', 'S'); // 'S' returns as string
    }

    /**
     * Build merge data dictionary from lead, application, and offer data.
     *
     * @return array<string, mixed>
     */
    private function buildMergeData(
        Lead $lead,
        Application $application,
        OfferLetter $offerLetter,
        ?array $customData = null,
    ): array {
        $programme = $application->programme;
        $institution = $lead->institution;

        $data = [
            // Lead fields
            'lead.first_name' => $lead->first_name ?? '',
            'lead.last_name' => $lead->last_name ?? '',
            'lead.full_name' => $lead->full_name ?? $lead->first_name ?? '',
            'lead.email' => $lead->email ?? '',
            'lead.mobile' => $lead->mobile ?? '',
            'lead.city' => $lead->city ?? '',
            'lead.state' => $lead->state ?? '',

            // Application fields
            'application.programme_name' => $programme?->name ?? 'Not Specified',
            'application.programme_code' => $programme?->code ?? '',
            'application.application_id' => $application->uuid ?? '',
            'application.applied_on' => $application->created_at?->format('d M Y') ?? '',
            'application.status' => $application->status?->value ?? '',

            // Offer fields
            'offer.offer_id' => $offerLetter->uuid ?? '',
            'offer.generated_on' => $offerLetter->generated_at?->format('d M Y') ?? now()->format('d M Y'),
            'offer.expires_on' => $offerLetter->expires_at?->format('d M Y') ?? '',
            'offer.status' => $offerLetter->status ?? '',

            // Institution fields
            'institution.name' => $institution?->name ?? '',
            'institution.address' => $institution?->address ?? '',
            'institution.city' => $institution?->city ?? '',
            'institution.contact_email' => $institution?->contact_email ?? '',
            'institution.contact_phone' => $institution?->contact_phone ?? '',
            'institution.principal_name' => $institution?->principal_name ?? 'Principal',
            'institution.website' => $institution?->website ?? '',
        ];

        // Merge custom data if provided
        if ($customData) {
            $data = array_merge($data, $customData);
        }

        return $data;
    }

    /**
     * Replace {{key}} merge tags with actual values.
     *
     * @param array<string, mixed> $data
     */
    private function replaceMergeTags(string $html, array $data): string
    {
        foreach ($data as $key => $value) {
            $tag = "{{" . $key . "}}";
            $html = str_replace($tag, (string) $value, $html);
        }

        // Remove any unreplaced merge tags
        $html = preg_replace('/\{\{[\w.]+\}\}/', '', $html);

        return $html;
    }

    /**
     * Get available merge tags for a template type.
     *
     * @return list<string>
     */
    public function getAvailableMergeTags(): array
    {
        return OfferLetterTemplate::getDefaultMergeTags();
    }
}
