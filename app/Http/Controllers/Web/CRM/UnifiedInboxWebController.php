<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\WhatsAppConversation;
use App\Services\CRM\Communication\UnifiedInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-CC-021 — Unified inbox for all inbound messages (web)
final class UnifiedInboxWebController extends Controller
{
    public function __construct(
        private readonly UnifiedInboxService $inboxService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.communication.send');

        $inbox = $this->inboxService->getInboxForCounsellor(Auth::user());
        $unreadCounts = $this->inboxService->getUnreadCounts(Auth::user());

        return view('crm.inbox.index', compact('inbox', 'unreadCounts'));
    }

    public function markRead(WhatsAppConversation $conversation): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $this->inboxService->markAsRead($conversation, Auth::user());

        return back()->with('success', 'Conversation marked as read.');
    }

    public function assign(WhatsAppConversation $conversation): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $validated = request()->validate([
            'counsellor_id' => ['required', 'exists:users,id'],
        ]);

        $conversation->update(['assigned_counsellor_id' => $validated['counsellor_id']]);

        return back()->with('success', 'Conversation assigned.');
    }

    /** Polling endpoint — returns unread badge counts as JSON for the web UI (NOT an API route). */
    public function unreadCounts(): JsonResponse
    {
        $this->authorize('crm.communication.send');

        return response()->json([
            'counts' => $this->inboxService->getUnreadCounts(Auth::user()),
        ]);
    }
}
