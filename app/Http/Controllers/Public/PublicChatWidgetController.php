<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\DTOs\CRM\CreateChatLeadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PublicChatLeadSubmissionRequest;
use App\Models\CRM\Institution;
use App\Services\CRM\Marketing\ChatWidgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

// BRD: CRM-LC-006 — Public controller for embeddable live chat widget
final class PublicChatWidgetController extends Controller
{
    public function __construct(
        private readonly ChatWidgetService $service,
    ) {}

    public function show(Institution $institution): View
    {
        abort_unless($institution->is_active, 404);

        return view('public.chat-widget.show', [
            'institution' => $institution,
        ]);
    }

    public function submit(PublicChatLeadSubmissionRequest $request, Institution $institution): JsonResponse
    {
        abort_unless($institution->is_active, 404);

        $chatLead = $this->service->captureLead(
            CreateChatLeadDTO::fromRequest($request->validated()),
            (int) $institution->id,
            $request->ip() ?? '0.0.0.0',
        );

        return response()->json([
            'success' => true,
            'data' => [
                'chat_lead_uuid' => $chatLead->uuid,
                'lead_uuid' => $chatLead->lead?->uuid,
            ],
            'message' => 'Thank you. Your chat enquiry has been captured.',
        ], 201);
    }
}
