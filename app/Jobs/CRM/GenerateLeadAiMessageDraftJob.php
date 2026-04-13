<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\LeadAiMessageDraftedEvent;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\AiCommunicationDraftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-003 — Async generation of AI communication drafts by lead and channel
final class GenerateLeadAiMessageDraftJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly string $leadUuid,
        public readonly string $channel,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return "ai-draft:{$this->leadUuid}:{$this->channel}";
    }

    public function handle(AiCommunicationDraftService $service): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            return;
        }

        $draft = $service->generateAndPersist($lead, $this->channel);

        Log::info('AI communication draft generated', [
            'lead_uuid' => $this->leadUuid,
            'draft_uuid' => $draft->uuid,
            'channel' => $this->channel,
            'model_version' => $draft->model_version,
        ]);

        LeadAiMessageDraftedEvent::dispatch($draft);
    }
}
