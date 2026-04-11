<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\DTOs\CRM\CreateKioskLeadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PublicKioskLeadSubmissionRequest;
use App\Models\CRM\Institution;
use App\Services\CRM\Marketing\KioskService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

// BRD: CRM-LC-013 — Public controller for touch-friendly walk-in kiosk lead capture
final class PublicKioskController extends Controller
{
    public function __construct(
        private readonly KioskService $service,
    ) {}

    public function show(Institution $institution): View
    {
        abort_unless($institution->is_active, 404);

        return view('public.kiosk.show', [
            'institution' => $institution,
        ]);
    }

    public function submit(PublicKioskLeadSubmissionRequest $request, Institution $institution): JsonResponse
    {
        abort_unless($institution->is_active, 404);

        $lead = $this->service->captureLead(
            CreateKioskLeadDTO::fromRequest($request->validated()),
            (int) $institution->id,
            $request->ip() ?? '0.0.0.0',
        );

        return response()->json([
            'success' => true,
            'data' => [
                'lead_uuid' => $lead->uuid,
            ],
            'message' => 'Walk-in enquiry captured successfully.',
        ], 201);
    }
}
