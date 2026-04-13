<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreChatLeadAgentReplyRequest;
use App\Http\Requests\Api\CRM\UpdateChatLeadHandoffRequest;
use App\Jobs\CRM\GenerateChatbotReplyJob;
use App\Models\CRM\ChatLead;
use App\Services\CRM\Marketing\ChatWidgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-LC-006 — CRM web controller for chat widget embed + captured sessions
final class ChatWidgetWebController extends Controller
{
    public function __construct(
        private readonly ChatWidgetService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.chat-widget.manage');

        $institution = $request->user()->institution;

        $chatLeads = $this->service->list(
            filters: $request->only(['session_id', 'lead_uuid', 'handoff_status']),
            perPage: 20,
        );

        $metrics = $this->service->metrics((int) $institution->id);

        return view('crm.marketing.chat-widget.index', [
            'chatLeads' => $chatLeads,
            'embedUrl' => route('public.chat-widget.show', ['institution' => $institution->uuid]),
            'submitUrl' => route('public.chat-widget.submit', ['institution' => $institution->uuid]),
            'institution' => $institution,
            'metrics' => $metrics,
        ]);
    }

    public function reply(StoreChatLeadAgentReplyRequest $request, ChatLead $chatLead): RedirectResponse
    {
        Gate::authorize('crm.chat-widget.manage');

        $this->service->appendStaffReply(
            $chatLead,
            (string) $request->validated('message'),
            $request->user(),
        );

        return back()->with('success', 'Agent reply added to chat transcript.');
    }

    public function updateHandoff(UpdateChatLeadHandoffRequest $request, ChatLead $chatLead): RedirectResponse
    {
        Gate::authorize('crm.chat-widget.manage');

        $validated = $request->validated();

        $this->service->updateHandoffStatus(
            $chatLead,
            (string) $validated['handoff_status'],
            isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
        );

        return back()->with('success', 'Chat handoff status updated.');
    }

    public function generateAiReply(ChatLead $chatLead): RedirectResponse
    {
        Gate::authorize('crm.chat-widget.manage');

        GenerateChatbotReplyJob::dispatch($chatLead->uuid);

        return back()->with('success', 'AI chatbot reply generation queued.');
    }
}
