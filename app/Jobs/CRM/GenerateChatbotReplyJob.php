<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\ChatbotEscalationEvent;
use App\Services\CRM\AI\ChatbotService;
use App\Services\CRM\Marketing\ChatWidgetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-006 — Async conversational AI response generation with escalation decisioning
final class GenerateChatbotReplyJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly string $chatLeadUuid,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return "chatbot-reply:{$this->chatLeadUuid}";
    }

    public function handle(ChatWidgetService $chatWidgetService, ChatbotService $chatbotService): void
    {
        $chatLead = $chatWidgetService->findByUuid($this->chatLeadUuid);

        if ($chatLead === null) {
            return;
        }

        $aiOutput = $chatbotService->generateReply($chatLead);
        $updated = $chatbotService->applyReply($chatLead, $aiOutput);

        Log::info('AI chatbot reply generated', [
            'chat_lead_uuid' => $this->chatLeadUuid,
            'intent' => $aiOutput['intent'],
            'escalated' => $aiOutput['escalate'],
        ]);

        if ($aiOutput['escalate'] && $aiOutput['escalation_reason'] !== null) {
            ChatbotEscalationEvent::dispatch($updated, $aiOutput['escalation_reason']);
        }
    }
}
