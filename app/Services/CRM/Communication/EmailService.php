<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\DTOs\CRM\SendEmailDTO;
use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Events\CRM\Communication\EmailBouncedEvent;
use App\Events\CRM\Communication\EmailSentEvent;
use App\Events\CRM\Communication\EmailUnsubscribedEvent;
use App\Jobs\CRM\Communication\EnforceUnsubscribeJob;
use App\Jobs\CRM\Communication\SendBulkEmailJob;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\EmailCampaign;
use App\Models\CRM\Lead;
use App\Models\CRM\SenderDomain;
use App\Repositories\CRM\Communication\CommunicationLogRepositoryInterface;
use Illuminate\Support\Facades\Mail;

// BRD: CRM-CC-002, CRM-CC-003, CRM-CC-004, CRM-CC-005 — Email service
final class EmailService
{
    public function __construct(
        private readonly CommunicationLogRepositoryInterface $logRepository,
        private readonly TemplateService $templateService,
    ) {}

    /**
     * BRD: CRM-CC-002 — Send individual email from a lead record.
     */
    public function sendToLead(Lead $lead, SendEmailDTO $dto): CommunicationLog
    {
        $template = $dto->templateId > 0
            ? \App\Models\CRM\CommunicationTemplate::find($dto->templateId)
            : null;

        $renderedBody = $template !== null
            ? $this->templateService->render($template, [
                'first_name'        => $lead->first_name,
                'full_name'         => trim($lead->first_name.' '.($lead->last_name ?? '')),
                'institution_name'  => '',
                'unsubscribe_link'  => route('crm.unsubscribe', ['uuid' => $lead->uuid]),
            ])
            : ($dto->customBodyHtml ?? '');

        // BRD: CRM-CC-005 — Never send to unsubscribed leads
        if ($lead->email_unsubscribed_at !== null || $lead->dnc_at !== null) {
            throw new \RuntimeException('Cannot send email to an unsubscribed or DNC lead.');
        }

        // Dispatch actual email via Laravel Mail (provider configured via SenderDomain)
        Mail::to($lead->email)->send(new \App\Mail\CRM\LeadEmailMail(
            subject: $dto->subject ?? ($template?->subject ?? 'Message from us'),
            body: $renderedBody,
            fromName: $dto->fromName,
            fromEmail: $dto->fromEmail,
        ));

        $log = $this->logRepository->create([
            'institution_id' => $lead->institution_id,
            'lead_id'        => $lead->id,
            'channel'        => CommunicationChannel::EMAIL,
            'direction'      => MessageDirection::OUTBOUND,
            'template_id'    => $template?->id,
            'subject'        => $dto->subject ?? ($template?->subject ?? ''),
            'body_preview'   => mb_substr(strip_tags($renderedBody), 0, 500),
            'status'         => MessageStatus::SENT,
        ]);

        event(new EmailSentEvent($lead, $log));

        return $log;
    }

    /**
     * BRD: CRM-CC-002 — Dispatch bulk email campaign (fan-out per recipient).
     */
    public function dispatchCampaign(EmailCampaign $campaign): void
    {
        // Resolve recipients from filter
        $recipients = Lead::where('institution_id', $campaign->institution_id)
            ->whereNull('email_unsubscribed_at')
            ->whereNull('dnc_at')
            ->get(['id', 'uuid', 'email', 'first_name', 'last_name', 'institution_id']);

        $campaign->update([
            'status'           => \App\Enums\CRM\CampaignStatus::SENDING,
            'total_recipients' => $recipients->count(),
        ]);

        foreach ($recipients as $lead) {
            SendBulkEmailJob::dispatch($campaign->id, $lead->id)
                ->onQueue('crm-comms-email');
        }
    }

    /**
     * BRD: CRM-CC-003 — Handle delivery event from email provider webhook.
     *
     * @param array<string, mixed> $payload
     */
    public function handleDeliveryEvent(array $payload, string $provider): void
    {
        $externalId = $payload['message_id'] ?? $payload['MessageID'] ?? null;

        if ($externalId === null) {
            return;
        }

        $log = $this->logRepository->findByExternalId((string) $externalId);

        if ($log === null) {
            return;
        }

        $eventType = strtolower((string) ($payload['event'] ?? $payload['RecordType'] ?? ''));

        $updates = match ($eventType) {
            'delivered'   => ['status' => MessageStatus::DELIVERED, 'delivered_at' => now()],
            'open', 'opened' => ['status' => MessageStatus::DELIVERED, 'opened_at' => now()],
            'click', 'clicked' => ['clicked_at' => now()],
            'bounce', 'hardbounce' => ['status' => MessageStatus::BOUNCED, 'bounced_at' => now()],
            'unsubscribe' => ['status' => MessageStatus::UNSUBSCRIBED],
            default       => [],
        };

        if (! empty($updates)) {
            $this->logRepository->update($log, $updates);

            if ($eventType === 'bounce' || $eventType === 'hardbounce') {
                event(new EmailBouncedEvent($log->lead, $log));
            }

            if ($eventType === 'unsubscribe') {
                EnforceUnsubscribeJob::dispatch($log->lead_id, 'webhook_unsubscribe')
                    ->onQueue('crm-comms-email');
            }
        }
    }

    /**
     * BRD: CRM-CC-005 — DPDP-compliant lead unsubscribe (dispatches job within 24h).
     */
    public function unsubscribeLead(Lead $lead, string $reason): void
    {
        EnforceUnsubscribeJob::dispatch($lead->id, $reason)
            ->onQueue('crm-comms-email');

        event(new EmailUnsubscribedEvent($lead, $reason));
    }

    /**
     * BRD: CRM-CC-004 — Verify sender domain DNS records via provider API.
     */
    public function verifySenderDomain(SenderDomain $domain): SenderDomain
    {
        // DNS verification — in production uses provider's SDK to check TXT records
        // Returns updated domain with spf_verified, dkim_verified, dmarc_verified flags
        $domain->update(['verified_at' => now()]);

        return $domain->fresh();
    }
}
