<?php

declare(strict_types=1);

namespace App\Services\CRM\Alumni;

use App\Enums\CRM\Alumni\ReferralRewardStatus;
use App\Enums\CRM\LeadSource;
use App\Models\CRM\Alumni\AlumniPipeline;
use App\Models\CRM\Alumni\AlumniReferralCampaign;
use App\Models\CRM\Alumni\AlumniReferralCode;
use App\Models\CRM\Lead;
use App\Models\CRM\LeadAttribution;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AL-002, CRM-AL-003 — Referral code generation, lead tagging, and reward accrual
final class AlumniReferralService
{
    // BRD: CRM-AL-002 — Generate a unique 8-char alphanumeric referral code for an alumni in a campaign
    public function generateCode(AlumniPipeline $alumni, AlumniReferralCampaign $campaign): AlumniReferralCode
    {
        $attempts = 0;

        while ($attempts < 5) {
            $code = strtoupper(str_pad(base_convert((string) crc32(uniqid('', true)), 10, 36), 8, '0', STR_PAD_LEFT));

            $exists = AlumniReferralCode::withoutGlobalScopes()
                ->where('institution_id', $campaign->institution_id)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return AlumniReferralCode::create([
                    'institution_id' => $campaign->institution_id,
                    'campaign_id'    => $campaign->id,
                    'alumni_id'      => $alumni->id,
                    'code'           => $code,
                    'is_active'      => true,
                    'reward_status'  => ReferralRewardStatus::Pending->value,
                ]);
            }

            $attempts++;
        }

        Log::error('AlumniReferralService: failed to generate unique code after 5 attempts', [
            'institution_id' => $campaign->institution_id,
            'campaign_id'    => $campaign->id,
            'alumni_id'      => $alumni->id,
        ]);

        throw new \RuntimeException('Unable to generate a unique referral code after 5 attempts.');
    }

    // BRD: CRM-AL-003 — Capture ?ref=CODE on public form submission and tag the lead
    // Wrapped in try-catch — any failure logs a warning but does NOT interrupt form submission.
    public function trackReferral(string $refCode, Lead $lead): void
    {
        try {
            $sanitised = strtoupper(preg_replace('/[^A-Z0-9]/i', '', substr($refCode, 0, 8)) ?? '');

            if ($sanitised === '') {
                return;
            }

            $referralCode = AlumniReferralCode::withoutGlobalScopes()
                ->where('institution_id', $lead->institution_id)
                ->where('code', $sanitised)
                ->where('is_active', true)
                ->with('campaign')
                ->first();

            if ($referralCode === null) {
                return;
            }

            // Validate campaign is still active
            $campaign = $referralCode->campaign;
            if ($campaign === null || $campaign->status->value !== 'active') {
                return;
            }

            $today = now()->startOfDay();
            if ($campaign->start_date > $today) {
                return;
            }
            if ($campaign->end_date !== null && $campaign->end_date < $today) {
                return;
            }

            // Tag the lead with alumni referral metadata
            $lead->referred_by_alumni_id  = $referralCode->alumni_id;
            $lead->referral_code          = $sanitised;
            $lead->referral_campaign_id   = $campaign->id;
            $lead->save();

            // Create LeadAttribution touchpoint for analytics
            LeadAttribution::create([
                'institution_id' => $lead->institution_id,
                'campus_id'      => $lead->campus_id,
                'lead_id'        => $lead->id,
                'touch_type'     => 'referral',
                'source'         => LeadSource::ALUMNI_REFERRAL->value,
                'touchpoint_at'  => now(),
                'is_first_touch' => false,
                'is_last_touch'  => true,
                'metadata'       => [
                    'ref_code'          => $sanitised,
                    'alumni_pipeline_id' => $referralCode->alumni_id,
                    'campaign_id'       => $campaign->id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('AlumniReferralService: trackReferral failed — lead created without referral tag', [
                'ref_code' => $refCode,
                'lead_id'  => $lead->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // BRD: CRM-AL-003 — Mark reward as earned when referred lead converts to enrolled student
    public function accrueReward(AlumniReferralCode $code): void
    {
        $code->increment('conversions_count');
        $code->update(['reward_status' => ReferralRewardStatus::Earned->value]);
    }

    // BRD: CRM-AL-003 — Summary stats for a campaign (used in analytics card)
    public function getStats(AlumniReferralCampaign $campaign): array
    {
        $codes = $campaign->codes()->withoutGlobalScopes()->get();

        $totalConversions = $codes->sum('conversions_count');
        $leadsReferred    = Lead::withoutGlobalScopes()
            ->where('referral_campaign_id', $campaign->id)
            ->count();

        return [
            'leads_referred'    => $leadsReferred,
            'conversions'       => $totalConversions,
            'conversion_rate'   => $leadsReferred > 0
                ? round(($totalConversions / $leadsReferred) * 100, 1)
                : 0.0,
        ];
    }
}
