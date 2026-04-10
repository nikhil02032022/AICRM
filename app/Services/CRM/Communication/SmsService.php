<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\CampaignStatus;
use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\DltTemplateStatus;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Enums\CRM\SmsGateway;
use App\Jobs\CRM\Communication\SendBulkSmsJob;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\DltTemplate;
use App\Models\CRM\Lead;
use App\Models\CRM\SmsCampaign;
use App\Repositories\CRM\Communication\CommunicationLogRepositoryInterface;
use App\Services\CRM\Communication\Gateways\SmsGatewayInterface;

// BRD: CRM-CC-006, CRM-CC-007, CRM-CC-009 — SMS service with gateway strategy pattern
final class SmsService
{
    /** @var array<string, SmsGatewayInterface> */
    private array $gateways = [];

    public function __construct(
        private readonly CommunicationLogRepositoryInterface $logRepository,
    ) {}

    public function registerGateway(SmsGateway $gateway, SmsGatewayInterface $adapter): void
    {
        $this->gateways[$gateway->value] = $adapter;
    }

    private function resolveGateway(SmsGateway $gateway): SmsGatewayInterface
    {
        if (! isset($this->gateways[$gateway->value])) {
            throw new \RuntimeException("SMS gateway [{$gateway->value}] not configured.");
        }

        return $this->gateways[$gateway->value];
    }

    /**
     * BRD: CRM-CC-006 — Send individual SMS to a single lead.
     */
    public function sendToLead(Lead $lead, string $message, DltTemplate $template): CommunicationLog
    {
        // BRD: CRM-CC-008 — Enforce approved DLT templates only
        if (! $template->canSend()) {
            throw new \RuntimeException('DLT template is not approved. Cannot send SMS.');
        }

        // BRD: CRM-CC-005 compatible — DNC check
        if ($lead->sms_unsubscribed_at !== null || $lead->dnc_at !== null) {
            throw new \RuntimeException('Cannot send SMS to an opted-out or DNC lead.');
        }

        $gateway = $this->resolveGateway($template->gateway);
        $result  = $gateway->send($lead->mobile, $message, $template->sender_id);

        $status = $result['success'] ? MessageStatus::SENT : MessageStatus::FAILED;

        $log = $this->logRepository->create([
            'institution_id' => $lead->institution_id,
            'lead_id'        => $lead->id,
            'channel'        => CommunicationChannel::SMS,
            'direction'      => MessageDirection::OUTBOUND,
            'template_id'    => $template->id,
            'body_preview'   => mb_substr($message, 0, 500),
            'status'         => $status,
            'external_id'    => $result['message_id'],
        ]);

        return $log;
    }

    /**
     * BRD: CRM-CC-006 — Dispatch bulk SMS campaign fan-out.
     */
    public function dispatchSmsCampaign(SmsCampaign $campaign): void
    {
        $leads = Lead::where('institution_id', $campaign->institution_id)
            ->whereNull('sms_unsubscribed_at')
            ->whereNull('dnc_at')
            ->get(['id', 'uuid', 'institution_id', 'mobile']);

        $campaign->update([
            'status'           => CampaignStatus::SENDING,
            'total_recipients' => $leads->count(),
        ]);

        foreach ($leads as $lead) {
            SendBulkSmsJob::dispatch($campaign->id, $lead->id)
                ->onQueue('crm-comms-sms');
        }
    }

    /**
     * BRD: CRM-CC-009 — Handle delivery receipt from SMS gateway webhook.
     *
     * @param array<string, mixed> $payload
     */
    public function handleDeliveryReceipt(array $payload, string $gateway): void
    {
        $gatewayEnum = SmsGateway::tryFrom(strtoupper($gateway));

        if ($gatewayEnum === null) {
            return;
        }

        $adapter  = $this->resolveGateway($gatewayEnum);
        $parsed   = $adapter->parseDeliveryReceipt($payload);
        $log      = $this->logRepository->findByExternalId($parsed['message_id']);

        if ($log === null) {
            return;
        }

        $status = match (strtoupper($parsed['status'])) {
            'DELIVERED' => MessageStatus::DELIVERED,
            'FAILED', 'UNDELIVERED' => MessageStatus::FAILED,
            default     => $log->status,
        };

        $this->logRepository->update($log, [
            'status'       => $status,
            'delivered_at' => $parsed['delivered_at'] ?? ($status === MessageStatus::DELIVERED ? now() : null),
        ]);
    }

    /**
     * BRD: CRM-CC-005 compatible — Mark lead as SMS DNC/opt-out.
     */
    public function optOutLead(Lead $lead): void
    {
        $lead->update([
            'sms_unsubscribed_at' => now(),
            'dnc_at'              => now(),
        ]);
    }
}
