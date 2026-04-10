<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\Lead;
use App\Notifications\CRM\HotLeadAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LQ-006 — Send immediate alert to assigned counsellor when a lead becomes HOT
final class SendHotLeadAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $leadUuid,
    ) {
        $this->onQueue('crm-notifications');
    }

    public function handle(): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->with('assignedCounsellor')
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            return;
        }

        $counsellor = $lead->assignedCounsellor;

        if ($counsellor === null) {
            // No counsellor assigned — log and skip; no one to notify
            Log::info('HOT lead alert skipped — no counsellor assigned', [
                'lead_uuid' => $this->leadUuid,
            ]);

            return;
        }

        // BRD: CRM-LQ-006 — Notify via both DB (in-app bell) and email (with proper template)
        $counsellor->notify(new HotLeadAlertNotification($lead));

        Log::info('HOT lead alert dispatched', [
            'lead_uuid' => $this->leadUuid,
            'counsellor_id' => $counsellor->id,
        ]);
    }
}
