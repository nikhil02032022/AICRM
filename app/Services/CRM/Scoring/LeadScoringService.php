<?php

declare(strict_types=1);

namespace App\Services\CRM\Scoring;

use App\DTOs\CRM\ScoreOverrideDTO;
use App\DTOs\CRM\UpdateScoringConfigDTO;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Events\CRM\ScoreChangedEvent;
use App\Models\CRM\InstitutionScoringConfig;
use App\Models\CRM\Lead;
use App\Models\CRM\ScoreOverride;
use App\Repositories\CRM\Scoring\ScoringConfigRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// BRD: CRM-LQ-001, CRM-LQ-002, CRM-LQ-004, CRM-LQ-005, CRM-LQ-006, CRM-LQ-007, CRM-LQ-008
final class LeadScoringService
{
    public function __construct(
        private readonly ScoringConfigRepositoryInterface $configRepository,
    ) {}

    // -----------------------------------------------------------------------
    // BRD: CRM-LQ-001 — Configurable rule-based lead scoring engine
    // -----------------------------------------------------------------------

    /**
     * Calculate the lead score against the given institution config.
     * Returns an integer 0–100. Does NOT persist; caller is responsible for saving.
     *
     * BRD: CRM-LQ-002 — Scoring parameters:
     *   profile_completeness, programme_interest, source_quality,
     *   engagement, consent, geographic, response_time
     */
    public function calculateScore(Lead $lead, InstitutionScoringConfig $config): int
    {
        $weights = array_merge(InstitutionScoringConfig::defaultWeights(), (array) $config->weights);

        $score = 0;

        // ── 1. Profile completeness (scaled to configured weight, max 25 by default) ──
        $score += $this->scoreProfileCompleteness($lead, $weights['profile_completeness']);

        // ── 2. Programme interest (max 20) ──
        $score += $this->scoreProgrammeInterest($lead, $weights['programme_interest']);

        // ── 3. Source quality (max 20) ──
        $score += $this->scoreSourceQuality($lead, $weights['source_quality']);

        // ── 4. Engagement signals (max 20) ──
        $score += $this->scoreEngagement($lead, $weights['engagement']);

        // ── 5. Consent (max 5) ──
        $score += $this->scoreConsent($lead, $weights['consent']);

        // ── 6. Geographic completeness (max 5) ──
        $score += $this->scoreGeographic($lead, $weights['geographic']);

        // ── 7. Response time (stub — Group E adds assigned_at column) ──
        // BRD: CRM-LQ-002 — Response time signal placeholder; activates in Group E
        $score += 0;

        return min(100, $score);
    }

    /**
     * Get (or create with defaults) the scoring config for an institution.
     * BRD: CRM-LQ-005
     */
    public function getScoringConfig(int $institutionId): InstitutionScoringConfig
    {
        $config = $this->configRepository->findByInstitution($institutionId);

        if ($config !== null) {
            return $config;
        }

        // Auto-create with defaults so new institutions work out of the box
        return $this->configRepository->upsert($institutionId, [
            'weights'        => InstitutionScoringConfig::defaultWeights(),
            'hot_threshold'  => 75,
            'warm_threshold' => 50,
        ]);
    }

    /**
     * Apply a manual score override from a counsellor.
     * BRD: CRM-LQ-007
     */
    public function applyManualOverride(Lead $lead, ScoreOverrideDTO $dto): ScoreOverride
    {
        $previousScore       = $lead->lead_score;
        $previousTemperature = $lead->temperature;

        // Persist the override audit record
        $override = ScoreOverride::create([
            'uuid'              => Str::uuid()->toString(),
            'lead_id'           => $lead->id,
            'overridden_by'     => $dto->actorId,
            'previous_score'    => $previousScore,
            'overridden_score'  => $dto->overriddenScore,
            'reason'            => $dto->reason,
        ]);

        // Derive new temperature from overridden score using the institution's configured thresholds
        $config          = $this->getScoringConfig($lead->institution_id);
        $newTemperature  = $this->deriveTemperature($dto->overriddenScore, $config);

        // Update the lead record
        $lead->update([
            'lead_score'               => $dto->overriddenScore,
            'temperature'              => $newTemperature->value,
            'score_manually_overridden' => true,
        ]);

        $lead->refresh();

        // BRD: CRM-CR-002 — No PII in logs
        Log::info('Lead score manually overridden', [
            'lead_uuid'      => $lead->uuid,
            'previous_score' => $previousScore,
            'new_score'      => $dto->overriddenScore,
            'actor_id'       => $dto->actorId,
        ]);

        // Fire ScoreChangedEvent so downstream listeners react
        ScoreChangedEvent::dispatch($lead, $previousScore, $dto->overriddenScore);

        // Fire temperature event if classification changed
        if ($newTemperature !== $previousTemperature) {
            LeadTemperatureChangedEvent::dispatch($lead, $previousTemperature, $newTemperature);
        }

        return $override;
    }

    /**
     * Update the scoring configuration for an institution.
     * BRD: CRM-LQ-001, CRM-LQ-005
     */
    public function updateConfig(int $institutionId, UpdateScoringConfigDTO $dto): InstitutionScoringConfig
    {
        return $this->configRepository->upsert($institutionId, [
            'weights'        => $dto->weights,
            'hot_threshold'  => $dto->hotThreshold,
            'warm_threshold' => $dto->warmThreshold,
        ]);
    }

    /**
     * Source quality report — average score and conversion rate by lead source.
     * BRD: CRM-LQ-008
     *
     * @return Collection<int, object{source: string, avg_score: float, total: int, converted: int, conversion_rate: float}>
     */
    public function getSourceQualityReport(int $institutionId): Collection
    {
        return Lead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereNull('deleted_at')
            ->toBase()
            ->selectRaw('source, AVG(lead_score) as avg_score, COUNT(*) as total,
                         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as converted',
                         [LeadStatus::ENROLLED->value])
            ->groupBy('source')
            ->orderByDesc('avg_score')
            ->get()
            ->map(function (object $row): object {
                $row->conversion_rate = $row->total > 0
                    ? round(($row->converted / $row->total) * 100, 1)
                    : 0.0;
                $row->avg_score = round((float) $row->avg_score, 1);

                return $row;
            });
    }

    // -----------------------------------------------------------------------
    // Derive temperature from score + institution thresholds
    // -----------------------------------------------------------------------

    /**
     * BRD: CRM-LQ-005 — Temperature derived from institution-configured thresholds.
     * LOST and CONVERTED are not set by the scoring engine — only via status transitions.
     */
    public function deriveTemperature(int $score, InstitutionScoringConfig $config): LeadTemperature
    {
        return match(true) {
            $score >= $config->hot_threshold  => LeadTemperature::HOT,
            $score >= $config->warm_threshold => LeadTemperature::WARM,
            default                            => LeadTemperature::COLD,
        };
    }

    // -----------------------------------------------------------------------
    // Private signal calculators
    // -----------------------------------------------------------------------

    /** BRD: CRM-LQ-002 — Demographic data completeness (5 fields, proportional to max weight). */
    private function scoreProfileCompleteness(Lead $lead, int $maxWeight): int
    {
        $fields = [
            $lead->email      !== null,
            $lead->city       !== null,
            $lead->state      !== null,
            ($lead->first_name !== null && $lead->last_name !== null),
            $lead->nationality !== null,
        ];

        $completed = count(array_filter($fields));
        $total     = count($fields);

        return (int) round(($completed / $total) * $maxWeight);
    }

    /** BRD: CRM-LQ-002 — Course interest match. */
    private function scoreProgrammeInterest(Lead $lead, int $maxWeight): int
    {
        return $lead->programmeInterests()->exists() ? $maxWeight : 0;
    }

    /**
     * BRD: CRM-LQ-002 — Source quality signal.
     * Points are distributed proportionally to configured max weight.
     */
    private function scoreSourceQuality(Lead $lead, int $maxWeight): int
    {
        // Tier ratios: TIER_1=1.0, TIER_2=0.75, TIER_3=0.6, TIER_4=0.5, DEFAULT=0.25
        $ratio = match($lead->source) {
            LeadSource::REFERRAL,
            LeadSource::WALK_IN           => 1.0,
            LeadSource::GOOGLE_ADS,
            LeadSource::FACEBOOK          => 0.75,
            LeadSource::IVR,
            LeadSource::WHATSAPP          => 0.60,
            LeadSource::WEBSITE_ORGANIC,
            LeadSource::QR_CODE           => 0.50,
            default                       => 0.25,
        };

        return (int) round($ratio * $maxWeight);
    }

    /**
     * BRD: CRM-LQ-002, CRM-LQ-004 — Engagement signals.
     * Partial signals active now; full ActivityLog integration in Group E.
     *
     * Points:
     *  - Status has advanced beyond NEW_ENQUIRY: 50% of maxWeight
     *  - Counsellor assigned: 25% of maxWeight
     *  - [Group E stub] ActivityLog events (email open, WhatsApp read, form revisit): 25% reserved
     */
    private function scoreEngagement(Lead $lead, int $maxWeight): int
    {
        $points = 0;

        // Status advancement signal
        if ($lead->status !== LeadStatus::NEW_ENQUIRY) {
            $points += (int) round(0.50 * $maxWeight);
        }

        // Counsellor assigned signal
        if ($lead->assigned_counsellor_id !== null) {
            $points += (int) round(0.25 * $maxWeight);
        }

        // BRD: CRM-LQ-002, CRM-LQ-004 — ActivityLog engagement signals (email opens, WhatsApp
        // reads, form revisits). Activated in Group E when ActivityLog entity is built.
        // Reserved weight: 25% of maxWeight (currently 0).

        return min($maxWeight, $points);
    }

    /** BRD: CRM-LQ-002, DPDP — Consent given signal. */
    private function scoreConsent(Lead $lead, int $maxWeight): int
    {
        return $lead->consent_given ? $maxWeight : 0;
    }

    /** BRD: CRM-LQ-002 — Geographic completeness. */
    private function scoreGeographic(Lead $lead, int $maxWeight): int
    {
        return $lead->state !== null ? $maxWeight : 0;
    }
}
