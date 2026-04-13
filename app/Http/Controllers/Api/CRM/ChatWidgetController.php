<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\CreateChatLeadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreChatLeadAgentReplyRequest;
use App\Http\Requests\Api\CRM\StoreChatLeadRequest;
use App\Http\Requests\Api\CRM\UpdateChatLeadHandoffRequest;
use App\Jobs\CRM\GenerateChatbotReplyJob;
use App\Http\Resources\CRM\ChatLeadResource;
use App\Models\CRM\ChatLead;
use App\Services\CRM\Marketing\ChatWidgetService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-LC-006 — API endpoints for external chat integration consumers
final class ChatWidgetController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ChatWidgetService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.chat-widget.manage');

        $chatLeads = $this->service->list(
            filters: $request->only(['session_id', 'lead_uuid']),
            perPage: (int) $request->input('per_page', 20),
        );

        return ChatLeadResource::collection($chatLeads);
    }

    public function store(StoreChatLeadRequest $request): JsonResponse
    {
        $chatLead = $this->service->captureLead(
            CreateChatLeadDTO::fromRequest($request->validated()),
            (int) $request->user()->institution_id,
            $request->ip() ?? '0.0.0.0',
        );

        return $this->success(
            new ChatLeadResource($chatLead),
            'Chat lead captured successfully.',
            201,
        );
    }

    public function show(ChatLead $chatLead): ChatLeadResource
    {
        Gate::authorize('crm.chat-widget.manage');

        return new ChatLeadResource($chatLead->loadMissing(['lead', 'assignedTo:id,name,email']));
    }

    public function reply(StoreChatLeadAgentReplyRequest $request, ChatLead $chatLead): JsonResponse
    {
        Gate::authorize('crm.chat-widget.manage');

        $updated = $this->service->appendStaffReply(
            $chatLead,
            (string) $request->validated('message'),
            $request->user(),
        );

        return $this->success(
            new ChatLeadResource($updated->loadMissing(['lead', 'assignedTo:id,name,email'])),
            'Agent reply added to chat transcript.',
        );
    }

    public function updateHandoff(UpdateChatLeadHandoffRequest $request, ChatLead $chatLead): JsonResponse
    {
        Gate::authorize('crm.chat-widget.manage');

        $validated = $request->validated();

        $updated = $this->service->updateHandoffStatus(
            $chatLead,
            (string) $validated['handoff_status'],
            isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
        );

        return $this->success(
            new ChatLeadResource($updated->loadMissing(['lead', 'assignedTo:id,name,email'])),
            'Chat handoff status updated.',
        );
    }

    public function generateAiReply(ChatLead $chatLead): JsonResponse
    {
        Gate::authorize('crm.chat-widget.manage');

        GenerateChatbotReplyJob::dispatch($chatLead->uuid);

        return $this->success(
            data: ['chat_lead_uuid' => $chatLead->uuid],
            message: 'AI chatbot reply generation queued.',
            status: 202,
        );
    }
}
