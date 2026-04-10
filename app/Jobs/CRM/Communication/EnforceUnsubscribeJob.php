<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Models\CRM\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-005 — DPDP: enforce unsubscribe within 24 hours
final class EnforceUnsubscribeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $leadId,
        public readonly string $reason,
    ) {
        $this->queue = 'crm-comms-email';
    }

    public function handle(): void
    {
        $lead = Lead::withoutGlobalScopes()->find($this->leadId);

        if ($lead === null) {
            return;
        }

        // Idempotent — only set if not already unsubscribed
        if ($lead->email_unsubscribed_at !== null) {
            return;
        }

        $lead->update(['email_unsubscribed_at' => now()]);

        // BRD: CRM-SA-009 — Audit log for unsubscribe
        \App\Models\CRM\AuditLog::create([
            'entity_type'    => Lead::class,
            'entity_id'      => (string) $lead->id,
            'action'         => 'email_unsubscribed',
            'old_values'     => ['email_unsubscribed_at' => null],
            'new_values'     => ['email_unsubscribed_at' => now(), 'reason' => $this->reason],
            'user_id'        => null, // system action
            'institution_id' => $lead->institution_id,
        ]);
    }
}
