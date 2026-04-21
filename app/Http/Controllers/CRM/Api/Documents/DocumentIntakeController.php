<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api\Documents;

use App\Enums\CRM\Documents\DocumentUploadChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Documents\UploadApplicationDocumentRequest;
use App\Models\CRM\Application;
use App\Models\CRM\Documents\DocumentChecklistItem;
use App\Services\CRM\Documents\ApplicationDocumentService;
use Illuminate\Http\JsonResponse;

// BRD: CRM-DM-002 — WhatsApp / email intake gateway for documents.
class DocumentIntakeController extends Controller
{
    public function __construct(private readonly ApplicationDocumentService $service) {}

    public function store(UploadApplicationDocumentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $application = Application::withoutGlobalScopes()->where('uuid', $data['application_uuid'])->firstOrFail();
        $item = DocumentChecklistItem::withoutGlobalScopes()->findOrFail($data['document_checklist_item_id']);
        $channel = DocumentUploadChannel::from($data['channel'] ?? DocumentUploadChannel::WHATSAPP->value);

        $doc = $this->service->upload($application, $item, $request->file('file'), $channel);

        return response()->json([
            'data' => [
                'uuid'   => $doc->uuid,
                'status' => $doc->status?->value,
            ],
        ], 201);
    }
}
