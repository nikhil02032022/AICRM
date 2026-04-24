<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Alumni;

use App\Models\CRM\Alumni\AlumniReferralCode;
use App\Models\CRM\Lead;
use App\Services\CRM\Alumni\AlumniReferralService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AL-003 — Accrues alumni referral reward when referred lead is enrolled
class AlumniReferralConvertedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $leadId)
    {
        $this->onQueue('crm-alumni');
    }

    public function handle(AlumniReferralService $service): void
    {
        $lead = Lead::withoutGlobalScopes()->find($this->leadId);

        if ($lead === null || $lead->referral_code === null || $lead->referral_campaign_id === null) {
            return;
        }

        $code = AlumniReferralCode::withoutGlobalScopes()
            ->where('code', $lead->referral_code)
            ->where('campaign_id', $lead->referral_campaign_id)
            ->where('institution_id', $lead->institution_id)
            ->first();

        if ($code === null) {
            Log::warning('AlumniReferralConvertedJob: referral code record not found', [
                'lead_id'       => $this->leadId,
                'referral_code' => $lead->referral_code,
                'campaign_id'   => $lead->referral_campaign_id,
            ]);

            return;
        }

        $service->accrueReward($code);
    }
}
