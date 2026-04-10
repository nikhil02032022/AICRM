<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Enums\CRM\CampaignStatus;
use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\DltTemplate;
use App\Models\CRM\Lead;
use App\Models\CRM\SmsCampaign;
use App\Services\CRM\Communication\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-006 — Per-recipient bulk SMS dispatch (idempotent)
final class SendBulkSmsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $campaignId,
        public readonly int $leadId,
    ) {
        $this->queue = 'crm-comms-sms';
    }

    public function uniqueId(): string
    {
        return "bulk_sms:{$this->campaignId}:{$this->leadId}";
    }

    public function handle(SmsService $smsService): void
    {
        $campaign = SmsCampaign::find($this->campaignId);
        $lead     = Lead::withoutGlobalScopes()->find($this->leadId);

        if ($campaign === null || $lead === null) {
            return;
        }

        if ($lead->sms_unsubscribed_at !== null || $lead->dnc_at !== null) {
            return;
        }

        $existing = CommunicationLog::where('loggable_type', SmsCampaign::class)
            ->where('loggable_id', $campaign->id)
            ->where('lead_id', $lead->id)
            ->exists();

        if ($existing) {
            return;
        }

        if ($campaign->dltTemplate === null || ! $campaign->dltTemplate->canSend()) {
            return;
        }

        try {
            $smsService->sendToLead($lead, $campaign->dltTemplate->template_body, $campaign->dltTemplate);
            $campaign->increment('sent_count');
        } catch (\Throwable $e) {
            $campaign->increment('failed_count');
            throw $e;
        }
    }
}
