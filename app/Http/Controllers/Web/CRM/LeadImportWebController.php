<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\ImportBatchStatus;
use App\Enums\CRM\IntegrationChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CRM\BulkLeadImportRequest;
use App\Models\CRM\LeadImportBatch;
use App\Repositories\CRM\Import\LeadImportBatchRepositoryInterface;
use App\Services\CRM\Import\BulkCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// BRD: CRM-LC-012 — Web controller for bulk CSV/XLSX lead import management
// BRD: CRM-LC-008 — Also used for portal CSV imports (same upload form, different channel)
final class LeadImportWebController extends Controller
{
    public function __construct(
        private readonly BulkCsvImportService $importService,
        private readonly LeadImportBatchRepositoryInterface $batchRepository,
    ) {}

    /**
     * GET /crm/imports — Import history dashboard
     */
    public function index(Request $request): View
    {
        Gate::authorize('crm.leads.import');

        $batches = $this->batchRepository->paginate(
            filters: $request->only(['channel', 'status']),
            institutionId: $request->user()->institution_id,
            perPage: 20,
        );

        return view('crm.imports.index', [
            'batches' => $batches,
            'channelOptions' => IntegrationChannel::optionsForSelect(),
            'statusOptions' => collect(ImportBatchStatus::cases())
                ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                ->all(),
        ]);
    }

    /**
     * GET /crm/imports/upload — Show upload form
     */
    public function upload(): View
    {
        Gate::authorize('crm.leads.import');

        return view('crm.imports.upload', [
            'channelOptions' => IntegrationChannel::optionsForSelect(),
        ]);
    }

    /**
     * POST /crm/imports — Process CSV/XLSX upload
     * BRD: CRM-LC-012 — Validates consent attestation before dispatching import
     */
    public function store(BulkLeadImportRequest $request): RedirectResponse
    {
        $batch = $this->importService->dispatch(
            file: $request->file('file'),
            channel: IntegrationChannel::from($request->validated('channel')),
            institutionId: $request->user()->institution_id,
            initiatedByUserId: $request->user()->id,
        );

        return redirect()
            ->route('crm.imports.index')
            ->with('success', "Import started — {$batch->total_rows} rows queued. We'll email you when it completes.");
    }

    /**
     * GET /crm/imports/{batch:uuid}/report — Download error report CSV from local storage
     * BRD: CRM-LC-012 — Error report download for the uploader
     */
    public function report(LeadImportBatch $batch): BinaryFileResponse|RedirectResponse
    {
        Gate::authorize('crm.leads.import');

        // Ensure the batch belongs to this institution (InstitutionScope already enforces it via model binding)
        if ($batch->error_report_path === null) {
            return back()->with('error', 'No error report is available for this import batch.');
        }

        // Stream the file directly from local storage — never expose the raw path
        return response()->download(
            Storage::disk('local')->path($batch->error_report_path),
            basename($batch->error_report_path),
        );
    }
}
