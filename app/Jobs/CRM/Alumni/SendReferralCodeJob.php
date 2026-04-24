<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Alumni;

use App\Models\CRM\Alumni\AlumniReferralCode;
use App\Models\CRM\Lead;
use App\Notifications\CRM\Alumni\ReferralCodeShareNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AL-002 — Sends referral code to alumni via email (queued)
class SendReferralCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $alumniReferralCodeId)
    {
        $this->onQueue('crm-comms-email');
    }

    public function handle(): void
    {
        $code = AlumniReferralCode::withoutGlobalScopes()
            ->with('alumni.lead')
            ->find($this->alumniReferralCodeId);

        if ($code === null) {
            Log::warning('SendReferralCodeJob: referral code not found', ['id' => $this->alumniReferralCodeId]);
            return;
        }

        $alumni = $code->alumni;
        if ($alumni === null) {
            return;
        }

        $lead = $alumni->lead;
        if ($lead === null) {
            Log::warning('SendReferralCodeJob: alumni has no associated lead', ['alumni_id' => $alumni->id]);
            return;
        }

        $lead->notify(new ReferralCodeShareNotification($code));

        $code->update(['shared_at' => now()]);
    }
}
