<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Enums\CRM\ConversationStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Enums\CRM\WaMessageType;
use App\Events\CRM\Communication\WhatsAppLeadCreatedEvent;
use App\Events\CRM\Communication\WhatsAppMessageReceivedEvent;
use App\Jobs\CRM\Communication\NotifyInboundMessageJob;
use App\Models\CRM\Lead;
use App\Models\CRM\WhatsAppConversation;
use App\Models\CRM\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-007 — Process inbound WhatsApp message; auto-create lead if not matched
final class ProcessInboundWhatsAppJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 5;

    /** @param array<string, mixed> $bspPayload */
    public function __construct(
        public readonly array $bspPayload,
        public readonly int $institutionId = 0,
    ) {
        $this->queue = 'crm-comms-whatsapp';
    }

    public function uniqueId(): string
    {
        $messageId = $this->bspPayload['entry'][0]['changes'][0]['value']['messages'][0]['id'] ?? '';

        return "wa_inbound:{$messageId}";
    }

    public function handle(): void
    {
        // Parse messages from BSP payload (Meta Cloud API format)
        foreach ($this->bspPayload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $msg) {
                    $this->processMessage($msg, $value);
                }
            }
        }
    }

    /** @param array<string, mixed> $msg @param array<string, mixed> $value */
    private function processMessage(array $msg, array $value): void
    {
        $fromPhone = $msg['from'] ?? '';
        $messageId = $msg['id'] ?? '';

        if (empty($fromPhone) || empty($messageId)) {
            return;
        }

        // Dedup by BSP message ID
        if (WhatsAppMessage::where('bsp_message_id', $messageId)->exists()) {
            return;
        }

        $institutionId = $this->institutionId;

        // Step 1: Find or create lead
        // NOTE: Mobile is encrypted — comparison done in PHP after decryption
        $lead = Lead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->get()
            ->first(fn ($l) => $l->mobile === $fromPhone);

        if ($lead === null) {
            // BRD: CRM-LC-007 — Auto-create lead; consent_given = false (DPDP — counsellor must confirm)
            $lead = Lead::create([
                'institution_id'  => $institutionId,
                'first_name'      => 'WhatsApp Lead',
                'mobile'          => $fromPhone,
                'source'          => LeadSource::WHATSAPP->value,
                'status'          => LeadStatus::NEW->value,
                'consent_given'   => false,
            ]);

            event(new WhatsAppLeadCreatedEvent($lead));
        }

        // Step 2: Find or create conversation
        $conversation = WhatsAppConversation::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('lead_id', $lead->id)
            ->where('status', '!=', ConversationStatus::EXPIRED->value)
            ->first();

        if ($conversation === null) {
            $conversation = WhatsAppConversation::create([
                'institution_id' => $institutionId,
                'lead_id'        => $lead->id,
                'wa_phone_number'=> $fromPhone,
                'wa_display_name'=> $value['contacts'][0]['profile']['name'] ?? null,
                'status'         => ConversationStatus::OPEN,
                'assigned_user_id' => $lead->assigned_counsellor_id,
            ]);
        }

        // Step 3: Store inbound message
        $waMessage = WhatsAppMessage::create([
            'conversation_id'=> $conversation->id,
            'institution_id' => $institutionId,
            'bsp_message_id' => $messageId,
            'direction'      => MessageDirection::INBOUND,
            'message_type'   => WaMessageType::from(strtoupper($msg['type'] ?? 'TEXT')),
            'body'           => $msg['text']['body'] ?? '',
            'status'         => MessageStatus::DELIVERED,
        ]);

        $conversation->update(['last_message_at' => now()]);

        event(new WhatsAppMessageReceivedEvent($waMessage));

        // Step 4: Notify assigned counsellor
        NotifyInboundMessageJob::dispatch('whatsapp', $conversation->id)
            ->onQueue('crm-notifications');
    }
}
