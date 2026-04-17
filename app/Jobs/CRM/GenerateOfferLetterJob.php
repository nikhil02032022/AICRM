<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\OfferLetter;
use App\Models\CRM\OfferLetterTemplate;
use App\Repositories\CRM\Application\OfferLetterTemplateRepositoryInterface;
use App\Services\CRM\Application\OfferLetterRenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Spipu\Html2Pdf\Exception\Html2PdfException;

/**
 * BRD: CRM-AP-012, CRM-AP-013 — Async offer letter PDF generation job
 * Keep idempotent: check if PDF already exists before regenerating
 */
final class GenerateOfferLetterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly OfferLetter $offerLetter,
    ) {}

    public function handle(
        OfferLetterRenderService $renderService,
        OfferLetterTemplateRepositoryInterface $templateRepository,
    ): void {
        // Idempotency: if PDF already generated, skip
        if ($this->offerLetter->pdf_path && Storage::disk('s3')->exists($this->offerLetter->pdf_path)) {
            return;
        }

        try {
            // Load relationships
            $this->offerLetter->load(['application', 'lead', 'application.programme']);

            // Get template for this offer type (default to 'offer' type)
            $template = $templateRepository->findActiveByType('offer')
                ?? $templateRepository->getOrCreateDefault('offer');

            // Render template to PDF
            $pdfContent = $renderService->renderToPdf(
                template: $template,
                lead: $this->offerLetter->lead,
                application: $this->offerLetter->application,
                offerLetter: $this->offerLetter,
            );

            $pdfPath = $this->storePdfSecurely($pdfContent);

            // Update offer letter with PDF path and status
            $this->offerLetter->update([
                'pdf_path' => $pdfPath,
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            // Mark template as used for tracking
            $template->markAsUsed();

        } catch (Html2PdfException $e) {
            // PDF generation error - log and retry
            \Log::error('Failed to render offer letter PDF: HTML2PDF error', [
                'offer_uuid' => $this->offerLetter->uuid,
                'error' => $e->getMessage(),
            ]);
            throw $e;

        } catch (\Exception $e) {
            // Log error and let Laravel retry mechanism handle backoff
            \Log::error('Failed to generate offer letter PDF', [
                'offer_uuid' => $this->offerLetter->uuid,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function storePdfSecurely(string $pdfContent): string
    {
        // Store encrypted on S3 in ap-south-1 (India) per DPDP requirements
        $path = "crm/offer-letters/{$this->offerLetter->institution_id}/{$this->offerLetter->uuid}.pdf";
        Storage::disk('s3')->put($path, $pdfContent, [
            'ServerSideEncryption' => 'AES256',
            'StorageClass' => 'STANDARD_IA', // Infrequent access for cost optimization
        ]);

        return $path;
    }
}

