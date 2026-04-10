<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Enums\CRM\CampaignStatus;
use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\EmailCampaign;
use App\Models\CRM\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

// BRD: CRM-CC-002 — Per-recipient bulk email dispatch (idempotent)
final class SendBulkEmailJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries       = 3;
    public int $backoff     = 30;
    public readonly string $queue;

    public function __construct(
        public readonly int $campaignId,
        public readonly int $leadId,
    ) {
        $this->queue = 'crm-comms-email';
    }

    // BRD: ShouldBeUnique — idempotency key prevents duplicate sends
    public function uniqueId(): string
    {
        return "bulk_email:{$this->campaignId}:{$this->leadId}";
    }

    public function handle(): void
    {
        $campaign = EmailCampaign::find($this->campaignId);
        $lead     = Lead::withoutGlobalScopes()->find($this->leadId);

        if ($campaign === null || $lead === null) {
            return;
        }

        // Skip unsubscribed leads
        if ($lead->email_unsubscribed_at !== null || $lead->dnc_at !== null) {
            return;
        }

        // Check dedup — don't re-send if log already exists
        $existing = CommunicationLog::where('loggable_type', EmailCampaign::class)
            ->where('loggable_id', $campaign->id)
            ->where('lead_id', $lead->id)
            ->exists();

        if ($existing) {
            return;
        }

        try {
            Mail::to($lead->email)->send(new \App\Mail\CRM\LeadEmailMail(
                subject: $campaign->subject,
                body: $campaign->template?->body_html ?? '',
                fromName: $campaign->from_name,
                fromEmail: $campaign->from_email,
            ));

            CommunicationLog::create([
                'institution_id' => $lead->institution_id,
                'lead_id'        => $lead->id,
                'loggable_type'  => EmailCampaign::class,
                'loggable_id'    => $campaign->id,
                'channel'        => CommunicationChannel::EMAIL,
                'direction'      => MessageDirection::OUTBOUND,
                'template_id'    => $campaign->template_id,
                'subject'        => $campaign->subject,
                'status'         => MessageStatus::SENT,
            ]);

            $campaign->increment('sent_count');
        } catch (\Throwable $e) {
            $campaign->increment('bounced_count');
            throw $e;
        }
    }
}
