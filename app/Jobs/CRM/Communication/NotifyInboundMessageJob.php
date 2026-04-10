<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Models\CRM\Lead;
use App\Models\CRM\WhatsAppConversation;
use App\Models\User;
use App\Notifications\CRM\InboundMessageNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-023 — Notify assigned counsellor on new inbound message
final class NotifyInboundMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 5;

    public function __construct(
        public readonly string $channel,
        public readonly int $entityId,
    ) {
        $this->queue = 'crm-notifications';
    }

    public function handle(): void
    {
        $counsellorId = null;

        if ($this->channel === 'whatsapp') {
            $conversation = WhatsAppConversation::withoutGlobalScopes()->find($this->entityId);
            $counsellorId = $conversation?->assigned_user_id ?? $conversation?->lead?->assigned_counsellor_id;
        }

        if ($counsellorId === null) {
            return;
        }

        $user = User::find($counsellorId);

        if ($user !== null) {
            $user->notify(new InboundMessageNotification($this->channel, $this->entityId));
        }
    }
}
