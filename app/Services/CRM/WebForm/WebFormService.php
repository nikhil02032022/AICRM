<?php

declare(strict_types=1);

namespace App\Services\CRM\WebForm;

use App\DTOs\CRM\CreateLeadDTO;
use App\DTOs\CRM\CreateWebFormDTO;
use App\Enums\CRM\LeadSource;
use App\Events\CRM\WebFormCreatedEvent;
use App\Events\CRM\WebFormSubmittedEvent;
use App\Models\CRM\Lead;
use App\Models\CRM\WebForm;
use App\Repositories\CRM\WebForm\WebFormRepositoryInterface;
use App\Services\CRM\Lead\LeadService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// BRD: CRM-LC-001 — Core service for web form CRUD, public submission, and QR generation
// BRD: CRM-LC-002 — Conditional field logic is schema-driven (stored in fields JSON)
// BRD: CRM-LC-009 — QR code generation using endroid/qr-code
// BRD: CRM-LC-015 — UTM params merged from public form submission data
final class WebFormService
{
    public function __construct(
        private readonly WebFormRepositoryInterface $repository,
        private readonly LeadService $leadService,
    ) {}

    /**
     * BRD: CRM-LC-001 — Create and persist a new web form configuration.
     */
    public function create(CreateWebFormDTO $dto, int $institutionId): WebForm
    {
        $embedToken = Str::random(64);

        $form = $this->repository->create($dto, $institutionId, $embedToken);

        Log::info('WebForm created', [
            'form_uuid'      => $form->uuid,
            'institution_id' => $form->institution_id,
        ]);

        WebFormCreatedEvent::dispatch($form);

        return $form;
    }

    /**
     * BRD: CRM-LC-001 — Update form configuration.
     *
     * @param array<string, mixed> $data
     */
    public function update(WebForm $form, array $data): WebForm
    {
        // Strip dangerous CSS if custom_css is updated
        if (isset($data['custom_css'])) {
            $data['custom_css'] = $this->sanitiseCss($data['custom_css']);
        }

        return $this->repository->update($form, $data);
    }

    /**
     * BRD: CRM-LC-001 — Soft-deactivate a form. Hard deletes are prohibited.
     */
    public function delete(WebForm $form): void
    {
        $this->repository->softDelete($form);
    }

    /**
     * BRD: CRM-LC-009 — Generate a QR code PNG binary for the form's public UTM URL.
     *
     * Uses endroid/qr-code v5 PngWriter. Returns raw PNG binary string.
     */
    public function generateQrCode(WebForm $form): string
    {
        $qr = new \Endroid\QrCode\QrCode($form->qrTargetUrl());
        $writer = new \Endroid\QrCode\Writer\PngWriter();

        return $writer->write($qr)->getString();
    }

    /**
     * BRD: CRM-LC-001 — Generate an iFrame embed HTML snippet for the form.
     */
    public function generateEmbedSnippet(WebForm $form): string
    {
        $url = htmlspecialchars($form->embedUrl(), ENT_QUOTES, 'UTF-8');

        return '<iframe src="' . $url . '" width="100%" height="600" frameborder="0" '
            . 'allow="clipboard-write" style="border:none;"></iframe>';
    }

    /**
     * BRD: CRM-LC-001 + CRM-LC-002 — Process a public form submission:
     *   1. Override source from the WebForm's sourced LeadSource
     *   2. Merge UTM params captured from URL by Alpine.js (LC-015)
     *   3. Delegate lead creation to LeadService (reuses all LC-011 + LC-018 + CR-001 logic)
     *   4. Fire WebFormSubmittedEvent
     *
     * @param  array<string, mixed>  $data  Validated data from PublicFormSubmissionRequest
     */
    public function handlePublicSubmission(WebForm $form, array $data, string $ip): Lead
    {
        // Force the source from the form's pre-configured source (overrides any submitted value)
        $data['source'] = $form->source->value;

        // Merge any UTM params that came with the form
        $data['consent_form_version'] = $form->consent_form_version;

        // Build a DTO and an anonymous actor-like object for LeadService
        $dto = new CreateLeadDTO(
            firstName:          $data['first_name'],
            lastName:           $data['last_name'],
            mobile:             $data['mobile'],
            email:              $data['email'] ?? null,
            source:             $data['source'],
            consentGiven:       true,                         // required:accepted in PublicFormSubmissionRequest
            consentIp:          $ip,
            consentFormVersion: $form->consent_form_version,
            campusId:           $form->campus_id,
            city:               $data['city'] ?? null,
            state:              $data['state'] ?? null,
            notes:              $data['notes'] ?? null,
            sourceUtmParams:    $data['source_utm_params'] ?? null,
            programmeIds:       null,
        );

        // Use a system pseudo-actor (no real user in public context)
        $actor = new PublicFormActor(institutionId: $form->institution_id);

        $lead = $this->leadService->create($dto, $actor);

        Log::info('Public form submission created lead', [
            'form_uuid' => $form->uuid,
            'lead_uuid' => $lead->uuid,
        ]);

        WebFormSubmittedEvent::dispatch($form, $lead);

        return $lead;
    }

    /**
     * Auto-generate a unique slug from a form name within the institution.
     */
    public function generateUniqueSlug(string $name, int $institutionId): string
    {
        return $this->repository->generateUniqueSlug($name, $institutionId);
    }

    /**
     * Sanitise custom_css — strips script tags and javascript: references.
     * BRD: Security — prevent stored XSS via custom CSS field.
     */
    private function sanitiseCss(string $css): string
    {
        // Remove <script> blocks if embedded
        $css = strip_tags($css);
        // Remove javascript: url references
        $css = preg_replace('/javascript\s*:/i', '', $css) ?? $css;
        // Remove expression() (IE CSS injection)
        $css = preg_replace('/expression\s*\(/i', '', $css) ?? $css;

        return $css;
    }
}
