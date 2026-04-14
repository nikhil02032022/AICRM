<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\InitiateDigiLockerRequest;
use App\Http\Resources\CRM\DigiLockerDocumentResource;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\DigiLockerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// BRD: CRM-DM-006 — DigiLocker integration API controller (Sanctum, external consumers only)
final class DigiLockerController extends Controller
{
    public function __construct(
        private readonly DigiLockerService $service
    ) {}

    /**
     * BRD: CRM-DM-006 — List DigiLocker documents (paginated)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $documents = $this->service->list(
            $request->user()->institution_id,
            (int) $request->get('per_page', 20),
        );

        return DigiLockerDocumentResource::collection($documents);
    }

    /**
     * BRD: CRM-DM-006 — Initiate a DigiLocker document request
     */
    public function store(InitiateDigiLockerRequest $request): JsonResponse
    {
        $lead = Lead::where('uuid', $request->validated('lead_uuid'))
            ->where('institution_id', $request->user()->institution_id)
            ->firstOrFail();

        $document = $this->service->initiateRequest(
            $lead,
            $request->validated('document_type'),
            (int) $request->validated('consent_record_id'),
        );

        return response()->json([
            'success' => true,
            'data'    => new DigiLockerDocumentResource($document),
            'message' => 'DigiLocker request initiated.',
        ], 201);
    }

    /**
     * BRD: CRM-DM-006 — Show a specific document record
     */
    public function show(string $uuid): JsonResponse
    {
        $document = $this->service->findByUuid($uuid);

        if ($document === null) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new DigiLockerDocumentResource($document),
        ]);
    }
}
