<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\DigiLockerDocument;
use App\Services\CRM\Integration\DigiLockerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-DM-006 — Async DigiLocker document verification via API Setu
final class VerifyDigiLockerDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries    = 3;
    public int $backoff  = 60;
    public int $timeout  = 30;

    public function __construct(
        private readonly int $documentId
    ) {}

    public function handle(DigiLockerService $service): void
    {
        $document = DigiLockerDocument::withoutGlobalScopes()->findOrFail($this->documentId);

        // BRD: CRM-DM-006 — Integration stub: replace with real API Setu DigiLocker call
        // The actual HTTP call to API Setu would retrieve the document URI and download reference.
        // For now we simulate a successful verification with a stub URI.
        $stubUri         = 'in.gov.digilocker.doc-' . $document->uuid;
        $stubStoragePath = 'crm/digilocker/' . $document->institution_id . '/' . $document->uuid . '.enc';

        $service->markVerified($document, $stubUri, $stubStoragePath);
    }

    public function failed(\Throwable $exception): void
    {
        $document = DigiLockerDocument::withoutGlobalScopes()->find($this->documentId);

        if ($document !== null) {
            // BRD: CRM-DM-006 — No PII in error logs
            app(DigiLockerService::class)->markFailed($document, 'Job failed after max retries');
        }
    }
}
