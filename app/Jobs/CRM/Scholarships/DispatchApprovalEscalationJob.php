<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ScholarshipAwardStatus;
use App\Events\CRM\Scholarships\ScholarshipStageAdvanced;
use App\Models\CRM\Scholarships\ScholarshipAward;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-FM-008 — Re-notify current-stage approvers when SLA is breached.
class DispatchApprovalEscalationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $slaHours = (int) config('crm_scholarships.escalation.sla_hours', 48);
        $cutoff = now()->subHours($slaHours);

        ScholarshipAward::withoutGlobalScopes()
            ->whereIn('status', [
                ScholarshipAwardStatus::COUNSELLOR_SUBMITTED->value,
                ScholarshipAwardStatus::MANAGER_APPROVED->value,
            ])
            ->where('updated_at', '<=', $cutoff)
            ->chunkById(200, function ($awards): void {
                foreach ($awards as $award) {
                    event(new ScholarshipStageAdvanced($award));
                    $award->touch();
                }
            });
    }
}
