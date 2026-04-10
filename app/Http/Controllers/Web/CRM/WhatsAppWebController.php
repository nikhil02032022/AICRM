<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\WhatsAppConversation;
use App\Services\CRM\Communication\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-CC-012 — WhatsApp shared inbox (web)
final class WhatsAppWebController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.communication.send');

        return view('crm.communication.whatsapp.index');
    }

    public function conversation(WhatsAppConversation $conversation): View
    {
        $this->authorize('crm.communication.send');

        $messages = $conversation->messages()->with('sender')->latest()->paginate(50);

        return view('crm.communication.whatsapp.conversation', compact('conversation', 'messages'));
    }

    public function sendMessage(WhatsAppConversation $conversation): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $message = request()->validate(['message' => ['required', 'string', 'max:4096']])['message'];

        $this->whatsAppService->sendMessage($conversation, $message, Auth::user());

        return back();
    }

    public function resolve(WhatsAppConversation $conversation): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $conversation->update(['status' => \App\Enums\CRM\ConversationStatus::RESOLVED]);

        return back()->with('success', 'Conversation resolved.');
    }
}
