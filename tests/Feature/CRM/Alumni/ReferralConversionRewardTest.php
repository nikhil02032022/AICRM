<?php

declare(strict_types=1);

// BRD: CRM-AL-003 — Feature tests for observer + job reward accrual on conversion

use App\Enums\CRM\Alumni\ReferralCampaignStatus;
use App\Enums\CRM\Alumni\ReferralRewardStatus;
use App\Jobs\CRM\Alumni\AlumniReferralConvertedJob;
use App\Models\CRM\Alumni\AlumniPipeline;
use App\Models\CRM\Alumni\AlumniReferralCampaign;
use App\Models\CRM\Alumni\AlumniReferralCode;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Alumni\AlumniReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();

    $this->alumni = AlumniPipeline::withoutGlobalScopes()->forceCreate([
        'institution_id' => $this->institution->id,
        'lead_id'        => null,
        'application_id' => null,
        'alumni_status'  => 'pending',
    ]);

    $this->campaign = AlumniReferralCampaign::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'name'           => 'Conversion Test Campaign',
        'start_date'     => now()->subDay()->toDateString(),
        'end_date'       => null,
        'reward_type'    => 'gift_voucher',
        'status'         => ReferralCampaignStatus::Active->value,
        'created_by'     => 1,
    ]);

    $this->referralCode = AlumniReferralCode::withoutGlobalScopes()->create([
        'institution_id'   => $this->institution->id,
        'campaign_id'      => $this->campaign->id,
        'alumni_id'        => $this->alumni->id,
        'code'             => 'CONV0001',
        'is_active'        => true,
        'conversions_count' => 0,
        'reward_status'    => ReferralRewardStatus::Pending->value,
    ]);

    // Lead tagged with the referral code
    $this->lead = Lead::factory()->create([
        'institution_id'       => $this->institution->id,
        'referred_by_alumni_id' => $this->alumni->id,
        'referral_code'        => 'CONV0001',
        'referral_campaign_id' => $this->campaign->id,
    ]);
});

it('ApplicationConversionReferralObserver dispatches AlumniReferralConvertedJob when a referred lead gets a conversion log', function (): void {
    Queue::fake();

    ApplicationConversionLog::withoutGlobalScopes()->create([
        'institution_id'  => $this->institution->id,
        'lead_uuid'       => $this->lead->uuid,
        'application_uuid' => Str::uuid()->toString(),
        'status'          => 'success',
        'attempted_at'    => now(),
    ]);

    Queue::assertPushed(AlumniReferralConvertedJob::class);
});

it('AlumniReferralConvertedJob::handle() increments conversions_count and sets reward_status to Earned', function (): void {
    // Run the job directly (no queue) to test handle() logic
    $job = new AlumniReferralConvertedJob($this->lead->id);
    $job->handle(new AlumniReferralService());

    $this->referralCode->refresh();

    expect($this->referralCode->conversions_count)->toBe(1);
    expect($this->referralCode->reward_status->value)->toBe(ReferralRewardStatus::Earned->value);
});
