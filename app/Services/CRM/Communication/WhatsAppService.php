<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\ConversationStatus;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Enums\CRM\WaMessageType;
use App\Events\CRM\Communication\WhatsAppLeadCreatedEvent;
use App\Events\CRM\Communication\WhatsAppMessageReceivedEvent;
use App\Events\CRM\Communication\WhatsAppMessageSentEvent;
use App\Jobs\CRM\Communication\ProcessInboundWhatsAppJob;
use App\Jobs\CRM\Communication\SendBulkWhatsAppJob;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\CommunicationTemplate;
use App\Models\CRM\Lead;
use App\Models\CRM\WhatsAppConversation;
use App\Models\CRM\WhatsAppMessage;
use App\Models\User;
use App\Repositories\CRM\Communication\CommunicationLogRepositoryInterface;
use App\Services\CRM\Communication\BSP\WhatsAppBspInterface;

// BRD: CRM-CC-010 to CRM-CC-015, CRM-LC-007 — WhatsApp service
final class WhatsAppService
{
    public function __construct(
        private readonly WhatsAppBspInterface $bsp,
        private readonly CommunicationLogRepositoryInterface $logRepository,
        private readonly TemplateService $templateService,
    ) {}

    /**
     * BRD: CRM-CC-011 — Send a template-based WhatsApp message to a lead.
     */
    public function sendTemplate(Lead $lead, string $templateName, array $params): CommunicationLog
    {
        $result = $this->bsp->sendTemplate($lead->mobile, $templateName, $params);

        $log = $this->logRepository->create([
            'institution_id' => $lead->institution_id,
            'lead_id'        => $lead->id,
            'channel'        => CommunicationChannel::WHATSAPP,
            'direction'      => MessageDirection::OUTBOUND,
            'body_preview'   => "Template: {$templateName}",
            'status'         => $result['success'] ? MessageStatus::SENT : MessageStatus::FAILED,
            'external_id'    => $result['message_id'],
        ]);

        event(new WhatsAppMessageSentEvent($lead, $log));

        return $log;
    }

    /**
     * BRD: CRM-CC-012 — Send a free-form session message from the shared inbox.
     */
    public function sendMessage(WhatsAppConversation $conversation, string $message, User $sender): WhatsAppMessage
    {
        $result = $this->bsp->sendMessage($conversation->wa_phone_number, $message);

        $waMessage = WhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'institution_id'  => $conversation->institution_id,
            'bsp_message_id'  => $result['message_id'],
            'direction'       => MessageDirection::OUTBOUND,
            'message_type'    => WaMessageType::TEXT,
            'body'            => $message,
            'status'          => $result['success'] ? MessageStatus::SENT : MessageStatus::FAILED,
            'sent_by'         => $sender->id,
        ]);

        if ($result['success']) {
            $conversation->update(['last_message_at' => now()]);
        }

        return $waMessage;
    }

    /**
     * BRD: CRM-LC-007 — Process inbound message → dispatch job for lead auto-creation.
     *
     * @param array<string, mixed> $bspPayload
     */
    public function handleInboundMessage(array $bspPayload): void
    {
        ProcessInboundWhatsAppJob::dispatch($bspPayload)->onQueue('crm-comms-whatsapp');
    }

    /**
     * BRD: CRM-CC-014 — Update WhatsApp message delivery/read status from BSP webhook.
     */
    public function updateMessageStatus(string $bspMessageId, MessageStatus $status): void
    {
        $message = WhatsAppMessage::where('bsp_message_id', $bspMessageId)->first();

        if ($message === null) {
            return;
        }

        $updates = ['status' => $status];

        if ($status === MessageStatus::DELIVERED) {
            $updates['delivered_at'] = now();
        } elseif ($status === MessageStatus::READ) {
            $updates['read_at'] = now();
        }

        $message->update($updates);
    }

    /**
     * BRD: CRM-CC-015 — Dispatch bulk WhatsApp broadcast to segmented leads.
     *
     * @param array<int> $leadIds
     */
    public function dispatchBroadcast(CommunicationTemplate $template, array $leadIds): void
    {
        foreach ($leadIds as $leadId) {
            SendBulkWhatsAppJob::dispatch($template->id, $leadId)->onQueue('crm-comms-whatsapp');
        }
    }
}
