<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Documents;

use App\Enums\CRM\Documents\DocumentUploadChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Documents\ReviewDocumentRequest;
use App\Http\Requests\CRM\Documents\UploadApplicationDocumentRequest;
use App\Models\CRM\Application;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\DocumentChecklistItem;
use App\Services\CRM\Documents\ApplicationDocumentService;
use App\Services\CRM\Documents\DocumentEncryptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-DM-002, DM-003, DM-004
class ApplicationDocumentController extends Controller
{
    public function __construct(
        private readonly ApplicationDocumentService $service,
        private readonly DocumentEncryptionManager $encryption,
    ) {}

    public function review(): View
    {
        Gate::authorize('document.review');
        $docs = ApplicationDocument::query()
            ->with(['item.checklist', 'application', 'lead'])
            ->whereIn('status', ['submitted', 'under_review', 'rejected'])
            ->orderByDesc('uploaded_at')
            ->paginate(20);

        return view('crm.documents.review.index', ['documents' => $docs]);
    }

    public function upload(UploadApplicationDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $application = Application::where('uuid', $data['application_uuid'])->firstOrFail();
        $item = DocumentChecklistItem::findOrFail($data['document_checklist_item_id']);
        $channel = isset($data['channel']) ? DocumentUploadChannel::from($data['channel']) : DocumentUploadChannel::PORTAL;

        $this->service->upload($application, $item, $request->file('file'), $channel);

        return back()->with('status', 'Document uploaded.');
    }

    public function decide(ReviewDocumentRequest $request, ApplicationDocument $document): RedirectResponse
    {
        Gate::authorize('document.review');
        $data = $request->validated();

        if ($data['decision'] === 'approve') {
            $this->service->approve($document, $data['comment'] ?? null);
        } elseif ($data['decision'] === 'reject') {
            $this->service->reject($document, $data['reason'] ?? 'No reason supplied.');
        } else {
            $this->service->requestReupload($document, $data['reason'] ?? 'Re-upload requested.');
        }

        return back()->with('status', 'Document decision recorded.');
    }

    public function download(ApplicationDocument $document): Response
    {
        Gate::authorize('document.review');
        if (! $document->storage_path) {
            abort(404);
        }
        $contents = $this->encryption->read($document->storage_path);

        return response($contents, 200, [
            'Content-Type'        => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.addslashes($document->original_filename ?: 'document.bin').'"',
        ]);
    }
}
