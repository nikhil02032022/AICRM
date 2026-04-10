<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LQ-006 — Cold lead downgrade → enter nurture drip sequence
// Group F (Communication Engine) will activate the drip workflow here
final class QueueNurtureSequenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $leadUuid,
    ) {
        $this->onQueue('crm-nurture');
    }

    public function handle(): void
    {
        // BRD: CRM-LQ-006 — Group F Communication Engine will implement drip campaign
        // enrollment here. This stub ensures the architecture is in place for Group F.
        Log::info('Nurture sequence queued for cold lead', [
            'lead_uuid' => $this->leadUuid,
        ]);
    }
}
