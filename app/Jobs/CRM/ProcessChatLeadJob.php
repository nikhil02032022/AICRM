<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\ChatLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-006 — Async post-processing hook for chat transcripts
final class ProcessChatLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $chatLeadUuid,
    ) {}

    public function handle(): void
    {
        $chatLead = ChatLead::withoutGlobalScopes()->where('uuid', $this->chatLeadUuid)->first();

        if ($chatLead === null || $chatLead->processed_at !== null) {
            return;
        }

        $chatLead->update(['processed_at' => now()]);
    }
}
