<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Communication;

use App\Models\CRM\WhatsAppConversation;
use App\Models\CRM\WhatsAppMessage;
use App\Services\CRM\Communication\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

// BRD: CRM-CC-012, CRM-CC-013 — Real-time WhatsApp conversation thread
final class ConversationThread extends Component
{
    #[Locked]
    public string $conversationUuid;

    public string $messageText = '';

    public function mount(string $conversationUuid): void
    {
        $this->conversationUuid = $conversationUuid;
        $this->authorize('crm.communication.send');
    }

    #[Computed]
    public function conversation(): WhatsAppConversation
    {
        return WhatsAppConversation::with(['lead', 'assignedCounsellor'])
            ->where('uuid', $this->conversationUuid)
            ->firstOrFail();
    }

    #[Computed]
    public function messages()
    {
        return WhatsAppMessage::where('conversation_id', $this->conversation->id)
            ->orderBy('created_at')
            ->get();
    }

    public function sendMessage(WhatsAppService $whatsAppService): void
    {
        $this->authorize('crm.communication.send');

        $this->validate([
            'messageText' => ['required', 'string', 'max:4096'],
        ]);

        $whatsAppService->sendMessage(
            $this->conversation,
            $this->messageText,
            Auth::user(),
        );

        $this->messageText = '';
        unset($this->messages);

        $this->dispatch('message-sent');
    }

    public function refreshMessages(): void
    {
        unset($this->messages);
    }

    public function render(): View
    {
        return view('livewire.crm.communication.conversation-thread');
    }
}
