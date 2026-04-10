<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\CommunicationTemplate;
use App\Models\CRM\Lead;
use App\Services\CRM\Communication\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-015 — Per-recipient WhatsApp broadcast (idempotent)
final class SendBulkWhatsAppJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $templateId,
        public readonly int $leadId,
        public readonly string $broadcastId = '',
    ) {
        $this->queue = 'crm-comms-whatsapp';
    }

    public function uniqueId(): string
    {
        return "bulk_wa:{$this->broadcastId}:{$this->leadId}";
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        $template = CommunicationTemplate::find($this->templateId);
        $lead     = Lead::withoutGlobalScopes()->find($this->leadId);

        if ($template === null || $lead === null) {
            return;
        }

        if ($lead->dnc_at !== null) {
            return;
        }

        $whatsAppService->sendTemplate($lead, $template->name, []);
    }
}
