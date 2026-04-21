<?php

declare(strict_types=1);

namespace App\Services\CRM\Portal;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Portal\PortalMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// BRD: CRM-SP-004 — Applicant-facing chat; bridges to CC-021 unified inbox so counsellor
// sees inbound portal messages alongside email/SMS/WhatsApp in their CRM inbox.
final class PortalChatService
{
    /**
     * Return the full chat thread for a lead, ordered chronologically.
     *
     * @return Collection<int, PortalMessage>
     */
    public function getThread(Lead $lead, Institution $institution): Collection
    {
        return PortalMessage::where('lead_uuid', $lead->uuid)
            ->where('institution_id', $institution->id)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Record a message sent by the applicant and log it to the CC-021 unified inbox.
     */
    public function sendFromApplicant(Lead $lead, Institution $institution, string $body): PortalMessage
    {
        $message = PortalMessage::create([
            'lead_uuid'      => $lead->uuid,
            'institution_id' => $institution->id,
            'direction'      => MessageDirection::INBOUND,
            'body'           => $body,
        ]);

        // Bridge to CC-021: counsellor sees this in their unified inbox under the PORTAL tab
        CommunicationLog::withoutGlobalScopes()->create([
            'institution_id' => $institution->id,
            'lead_id'        => $lead->id,
            'loggable_type'  => PortalMessage::class,
            'loggable_id'    => $message->id,
            'channel'        => CommunicationChannel::PORTAL,
            'direction'      => MessageDirection::INBOUND,
            'body_preview'   => mb_substr(strip_tags($body), 0, 150),
            'status'         => MessageStatus::DELIVERED,
        ]);

        return $message;
    }

    /**
     * Mark all unread counsellor messages as read by the applicant.
     * Called when the applicant opens the chat thread.
     */
    public function markOutboundRead(Lead $lead, Institution $institution): void
    {
        PortalMessage::where('lead_uuid', $lead->uuid)
            ->where('institution_id', $institution->id)
            ->where('direction', MessageDirection::OUTBOUND->value)
            ->whereNull('applicant_read_at')
            ->update(['applicant_read_at' => Carbon::now()]);
    }

    /**
     * Count of counsellor messages the applicant has not yet read.
     */
    public function unreadOutboundCount(Lead $lead, Institution $institution): int
    {
        return PortalMessage::where('lead_uuid', $lead->uuid)
            ->where('institution_id', $institution->id)
            ->where('direction', MessageDirection::OUTBOUND->value)
            ->whereNull('applicant_read_at')
            ->count();
    }
}
