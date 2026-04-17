<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Models\CRM\OfferLetter;

// BRD: CRM-AP-013 — Communication Engine integration for offer delivery
final class CommunicationEngineService
{
    /**
     * Send offer letter via the specified channel (email/whatsapp).
     * Returns: ['status' => 'sent'|'pending'|'failed', 'message_id' => string|null]
     */
    public function sendOfferLetter(OfferLetter $offerLetter, string $channel = 'email'): array
    {
        // TODO: Integrate with actual Communication Engine (email/WhatsApp dispatch)
        // For now, simulate success
        return [
            'status' => 'sent',
            'message_id' => uniqid('msg_', true),
        ];
    }
}
