<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Enums\CRM\LeadStatus;
use App\Models\CRM\CounsellorAssignmentConfig;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Notifications\CRM\LeadEscalationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

// BRD: CRM-EC-009 — Escalate unactioned leads past the configured threshold
// Idempotent: checks status_changed_at before each dispatch — safe to run multiple times
final class EscalateUnactionedLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public string $queue = 'crm-notifications';

    /**
     * Early-stage statuses that should be escalated if unactioned.
     *
     * @var list<string>
     */
    private const ESCALATION_STAGES = [
        LeadStatus::NEW_ENQUIRY->value,
        LeadStatus::CONTACTED->value,
        LeadStatus::COUNSELLING_SCHEDULED->value,
    ];

    public function handle(): void
    {
        // Fetch all institution configs that have escalation configured
        $configs = CounsellorAssignmentConfig::withoutGlobalScopes()
            ->whereNotNull('escalation_to_user_id')
            ->get();

        foreach ($configs as $config) {
            $threshold = now()->subHours($config->escalation_hours);

            $stalledLeads = Lead::withoutGlobalScopes()
                ->where('institution_id', $config->institution_id)
                ->whereIn('status', self::ESCALATION_STAGES)
                ->where('status_changed_at', '<=', $threshold)
                ->whereNull('deleted_at')
                ->get();

            if ($stalledLeads->isEmpty()) {
                continue;
            }

            $escalationTarget = User::find($config->escalation_to_user_id);

            if ($escalationTarget === null) {
                continue;
            }

            foreach ($stalledLeads as $lead) {
                // BRD: CRM-CR-002 — No PII in logs
                Log::info('Lead escalation triggered', [
                    'lead_uuid' => $lead->uuid,
                    'institution' => $config->institution_id,
                    'hours_stalled' => now()->diffInHours($lead->status_changed_at),
                ]);

                // In-app + email notification to the escalation target
                $escalationTarget->notify(
                    new LeadEscalationNotification($lead)
                );
            }
        }
    }
}
