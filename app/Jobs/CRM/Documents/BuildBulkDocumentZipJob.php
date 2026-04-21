<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Documents;

use App\Enums\CRM\Documents\BulkDownloadStatus;
use App\Enums\CRM\Documents\DocumentStatus;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\DocumentBulkDownloadJob;
use App\Services\CRM\Documents\DocumentEncryptionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

// BRD: CRM-DM-009 — Build a signed-URL zip bundle of documents for an application or programme batch.
class BuildBulkDocumentZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $jobId) {}

    public function handle(DocumentEncryptionManager $encryption): void
    {
        $job = DocumentBulkDownloadJob::withoutGlobalScopes()->find($this->jobId);
        if (! $job || $job->status !== BulkDownloadStatus::QUEUED) {
            return;
        }

        try {
            $job->status = BulkDownloadStatus::PROCESSING;
            $job->save();

            $zipDisk = (string) config('crm_documents.bulk_download.zip_disk', 'local');
            $zipRoot = (string) config('crm_documents.bulk_download.zip_root', storage_path('app/private/crm_document_zips'));
            if (! is_dir($zipRoot)) {
                @mkdir($zipRoot, 0775, true);
            }

            $filename = "bulk_{$job->uuid}.zip";
            $absZipPath = rtrim($zipRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

            $zip = new ZipArchive();
            if ($zip->open($absZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to open zip archive for writing.');
            }

            $query = ApplicationDocument::withoutGlobalScopes()
                ->whereIn('status', [
                    DocumentStatus::SUBMITTED->value,
                    DocumentStatus::UNDER_REVIEW->value,
                    DocumentStatus::VERIFIED->value,
                ])
                ->whereNotNull('storage_path');

            if ($job->scope === 'application') {
                $query->where('application_uuid', $job->target_ref);
            } else {
                $query->whereIn('application_uuid', function ($q) use ($job): void {
                    $q->select('uuid')->from('applications')->where('programme_id', (int) $job->target_ref);
                });
            }

            $count = 0;
            $query->chunkById(100, function ($docs) use ($zip, $encryption, &$count): void {
                foreach ($docs as $doc) {
                    try {
                        $plain = $encryption->read($doc->storage_path);
                    } catch (Throwable $e) {
                        continue;
                    }
                    $name = sprintf('%s/%s_%s', $doc->application_uuid, $doc->id, $doc->original_filename ?: 'document.bin');
                    $zip->addFromString($name, $plain);
                    $count++;
                }
            });

            $zip->close();

            $storedPath = 'crm_document_zips/'.$filename;
            $job->status = BulkDownloadStatus::READY;
            $job->zip_path = $storedPath;
            $job->zip_size_bytes = (int) @filesize($absZipPath);
            $job->file_count = $count;
            $job->completed_at = now();
            $job->expires_at = now()->addMinutes((int) config('crm_documents.bulk_download.ttl_minutes', 60));
            $job->save();
            // The zipDisk is informational for now; file is stored in the configured zip_root.
            unset($zipDisk);
        } catch (Throwable $e) {
            $job->status = BulkDownloadStatus::FAILED;
            $job->failure_reason = mb_substr($e->getMessage(), 0, 480);
            $job->save();
            throw $e;
        }
    }
}
