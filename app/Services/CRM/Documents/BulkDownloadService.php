<?php

declare(strict_types=1);

namespace App\Services\CRM\Documents;

use App\Enums\CRM\Documents\BulkDownloadStatus;
use App\Enums\CRM\Documents\DocumentStatus;
use App\Jobs\CRM\Documents\BuildBulkDocumentZipJob;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\DocumentBulkDownloadJob;
use DomainException;
use Illuminate\Support\Facades\Auth;

// BRD: CRM-DM-009 — Bulk document download orchestration.
final class BulkDownloadService
{
    public function queue(string $scope, string $targetRef): DocumentBulkDownloadJob
    {
        if (! in_array($scope, ['application', 'programme_batch'], true)) {
            throw new DomainException('Unsupported bulk-download scope.');
        }

        $count = $this->countFiles($scope, $targetRef);
        $max = (int) config('crm_documents.bulk_download.max_files', 2000);
        if ($count > $max) {
            throw new DomainException("Requested set ({$count}) exceeds limit ({$max}).");
        }

        $job = DocumentBulkDownloadJob::create([
            'institution_id' => Auth::user()?->institution_id,
            'requested_by'   => Auth::id() ?? 0,
            'scope'          => $scope,
            'target_ref'     => $targetRef,
            'status'         => BulkDownloadStatus::QUEUED->value,
            'file_count'     => $count,
        ]);

        BuildBulkDocumentZipJob::dispatch($job->id);

        return $job;
    }

    public function countFiles(string $scope, string $targetRef): int
    {
        $query = ApplicationDocument::query()
            ->whereIn('status', [DocumentStatus::SUBMITTED->value, DocumentStatus::UNDER_REVIEW->value, DocumentStatus::VERIFIED->value])
            ->whereNotNull('storage_path');

        if ($scope === 'application') {
            $query->where('application_uuid', $targetRef);
        } else {
            $query->whereIn('application_uuid', function ($q) use ($targetRef): void {
                $q->select('uuid')->from('applications')->where('programme_id', $targetRef);
            });
        }

        return (int) $query->count();
    }
}
