<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\AgentCommsChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreAgentCommsRequest;
use App\Services\CRM\Agent\AgentCommsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AG-008 — Agent bulk communications web controller (session auth, Blade views)
final class AgentCommsWebController extends Controller
{
    public function __construct(
        private readonly AgentCommsService $service
    ) {}

    /**
     * BRD: CRM-AG-008 — Bulk comms compose screen and history log
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $logs = $this->service->list($user->institution_id);

        return view('crm.agents.comms', compact('logs'));
    }

    /**
     * BRD: CRM-AG-008 — Dispatch bulk communication to selected agents
     */
    public function store(StoreAgentCommsRequest $request): RedirectResponse
    {
        $user      = $request->user();
        $validated = $request->validated();

        $this->service->send(
            institutionId:      $user->institution_id,
            campusId:           (int) ($user->campus_id ?? 0),
            sentByUserId:       $user->id,
            channel:            AgentCommsChannel::from($validated['channel']),
            messageBody:        $validated['message_body'],
            recipientAgentIds:  $validated['recipient_agent_ids'],
            subject:            $validated['subject'] ?? null,
        );

        return back()->with('success', 'Bulk communication queued for delivery to agents.');
    }
}
