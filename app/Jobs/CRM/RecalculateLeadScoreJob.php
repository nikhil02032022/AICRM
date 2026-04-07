<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LQ-001 — Recalculate lead score based on engagement history, profile completeness, and source
final class RecalculateLeadScoreJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly string $leadUuid,
    ) {
        $this->onQueue('default');
    }

    /** Unique key prevents duplicate score recalculations for the same lead. */
    public function uniqueId(): string
    {
        return "recalc-score:{$this->leadUuid}";
    }

    public function handle(): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            // Lead deleted before job ran — safe to discard
            return;
        }

        // BRD: CRM-LQ-001 — Score algorithm stub; AI scoring engine replaces this in Phase 2
        $score = $this->calculateBasicScore($lead);

        $lead->update([
            'lead_score'  => $score,
            'temperature' => \App\Enums\CRM\LeadTemperature::fromScore($score)->value,
        ]);

        // BRD: CRM-CR-002 — No PII in logs
        Log::info('Lead score recalculated', [
            'lead_uuid' => $this->leadUuid,
            'new_score' => $score,
        ]);
    }

    private function calculateBasicScore(Lead $lead): int
    {
        $score = 0;

        // Profile completeness signals (max 40 pts)
        if ($lead->email !== null) {
            $score += 10;
        }
        if ($lead->city !== null) {
            $score += 10;
        }
        if ($lead->programmeInterests()->exists()) {
            $score += 20;
        }

        // Source quality signals (max 30 pts)
        $score += match($lead->source) {
            \App\Enums\CRM\LeadSource::REFERRAL,
            \App\Enums\CRM\LeadSource::WALK_IN    => 30,
            \App\Enums\CRM\LeadSource::GOOGLE_ADS,
            \App\Enums\CRM\LeadSource::FACEBOOK   => 20,
            \App\Enums\CRM\LeadSource::IVR,
            \App\Enums\CRM\LeadSource::WHATSAPP   => 15,
            default                               => 10,
        };

        // Consent given (max 10 pts)
        if ($lead->consent_given) {
            $score += 10;
        }

        return min(100, $score);
    }
}
