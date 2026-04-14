<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\InitiateAadhaarKycRequest;
use App\Http\Resources\CRM\AadhaarEkycLogResource;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\AadhaarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// BRD: CRM-DM-007 — Aadhaar eKYC API controller (Sanctum, external consumers only)
final class AadhaarController extends Controller
{
    public function __construct(
        private readonly AadhaarService $service
    ) {}

    /**
     * BRD: CRM-DM-007 — List Aadhaar eKYC logs (paginated)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = $this->service->list(
            $request->user()->institution_id,
            (int) $request->get('per_page', 20),
        );

        return AadhaarEkycLogResource::collection($logs);
    }

    /**
     * BRD: CRM-DM-007 — Initiate Aadhaar eKYC session (mobile app triggers this)
     */
    public function store(InitiateAadhaarKycRequest $request): JsonResponse
    {
        $lead = Lead::where('uuid', $request->validated('lead_uuid'))
            ->where('institution_id', $request->user()->institution_id)
            ->firstOrFail();

        $log = $this->service->initiate($lead, $request->ip() ?? '0.0.0.0');

        return response()->json([
            'success' => true,
            'data'    => new AadhaarEkycLogResource($log),
            'message' => 'Aadhaar eKYC initiated. OTP sent.',
        ], 201);
    }

    /**
     * BRD: CRM-DM-007 — Show a specific eKYC log
     */
    public function show(string $uuid): JsonResponse
    {
        $log = $this->service->findByUuid($uuid);

        if ($log === null) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AadhaarEkycLogResource($log),
        ]);
    }
}
