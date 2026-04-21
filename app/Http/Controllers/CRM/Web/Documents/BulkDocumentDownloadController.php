<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Documents;

use App\Enums\CRM\Documents\BulkDownloadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Documents\RequestBulkDownloadRequest;
use App\Models\CRM\Documents\DocumentBulkDownloadJob;
use App\Services\CRM\Documents\BulkDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-DM-009
class BulkDocumentDownloadController extends Controller
{
    public function __construct(private readonly BulkDownloadService $service) {}

    public function queue(RequestBulkDownloadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $job = $this->service->queue($data['scope'], $data['target_ref']);

        return back()->with('status', 'Bundle queued: '.$job->uuid);
    }

    public function status(DocumentBulkDownloadJob $job): JsonResponse
    {
        Gate::authorize('document.bulk_download');

        return response()->json([
            'uuid'       => $job->uuid,
            'status'     => $job->status?->value,
            'file_count' => $job->file_count,
            'expires_at' => optional($job->expires_at)->toIso8601String(),
        ]);
    }

    public function download(DocumentBulkDownloadJob $job): Response
    {
        Gate::authorize('document.bulk_download');
        if ($job->status !== BulkDownloadStatus::READY || ! $job->zip_path) {
            abort(404);
        }
        if ($job->expires_at && $job->expires_at->isPast()) {
            abort(410);
        }

        $abs = rtrim((string) config('crm_documents.bulk_download.zip_root', storage_path('app/private/crm_document_zips')), DIRECTORY_SEPARATOR)
             .DIRECTORY_SEPARATOR.basename($job->zip_path);
        if (! is_file($abs)) {
            abort(404);
        }
        $contents = (string) file_get_contents($abs);

        return response($contents, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="bulk_'.$job->uuid.'.zip"',
        ]);
    }
}
