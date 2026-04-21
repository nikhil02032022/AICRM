<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Portal;

use App\Http\Controllers\Controller;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Portal\PortalChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SP-004 — Applicant ↔ counsellor chat within the portal
final class PortalChatController extends Controller
{
    public function __construct(private readonly PortalChatService $chatService) {}

    /**
     * Display the chat thread and mark counsellor messages as read.
     * GET /portal/chat
     */
    public function index(Request $request): View
    {
        [$lead, $institution] = $this->resolveSession($request);

        $this->chatService->markOutboundRead($lead, $institution);

        $messages = $this->chatService->getThread($lead, $institution);

        return view('portal.chat.index', [
            'messages'    => $messages,
            'applicant'   => $lead,
        ]);
    }

    /**
     * Store a new message from the applicant.
     * POST /portal/chat
     */
    public function store(Request $request): RedirectResponse
    {
        [$lead, $institution] = $this->resolveSession($request);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $this->chatService->sendFromApplicant($lead, $institution, $validated['body']);

        return redirect()->route('portal.chat.index')
            ->with('success', 'Message sent.');
    }

    /** @return array{0: Lead, 1: Institution} */
    private function resolveSession(Request $request): array
    {
        /** @var \App\Models\CRM\Portal\PortalSession $session */
        $session = $request->attributes->get('portal_session');

        /** @var Institution $institution */
        $institution = $request->attributes->get('portal_institution');

        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $session->lead_uuid)
            ->firstOrFail();

        return [$lead, $institution];
    }
}
