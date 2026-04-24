<?php

declare(strict_types=1);

// BRD: CRM-AL-003 — Feature tests for ?ref=CODE capture on public form submission

use App\Enums\CRM\Alumni\ReferralCampaignStatus;
use App\Enums\CRM\Alumni\ReferralRewardStatus;
use App\Models\CRM\Alumni\AlumniPipeline;
use App\Models\CRM\Alumni\AlumniReferralCampaign;
use App\Models\CRM\Alumni\AlumniReferralCode;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Alumni\AlumniReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();
    $this->service     = new AlumniReferralService();

    $this->alumni = AlumniPipeline::withoutGlobalScopes()->forceCreate([
        'institution_id' => $this->institution->id,
        'lead_id'        => null,
        'application_id' => null,
        'alumni_status'  => 'pending',
    ]);

    $this->campaign = AlumniReferralCampaign::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'name'           => 'Active Referral Campaign',
        'start_date'     => now()->subDay()->toDateString(),
        'end_date'       => null,
        'reward_type'    => 'gift_voucher',
        'status'         => ReferralCampaignStatus::Active->value,
        'created_by'     => 1,
    ]);

    $this->validCode = AlumniReferralCode::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'campaign_id'    => $this->campaign->id,
        'alumni_id'      => $this->alumni->id,
        'code'           => 'VALID001',
        'is_active'      => true,
        'reward_status'  => ReferralRewardStatus::Pending->value,
    ]);

    $this->lead = Lead::factory()->create([
        'institution_id'      => $this->institution->id,
        'referred_by_alumni_id' => null,
        'referral_code'       => null,
        'referral_campaign_id' => null,
    ]);
});

it('trackReferral() tags a lead submitted with a valid ref code', function (): void {
    $this->service->trackReferral('VALID001', $this->lead);

    $this->lead->refresh();

    expect($this->lead->referred_by_alumni_id)->toBe($this->alumni->id);
    expect($this->lead->referral_code)->toBe('VALID001');
    expect($this->lead->referral_campaign_id)->toBe($this->campaign->id);
});

it('lead submitted with an expired ref code is created without referral tags', function (): void {
    $expiredCampaign = AlumniReferralCampaign::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'name'           => 'Expired Campaign',
        'start_date'     => now()->subMonth()->toDateString(),
        'end_date'       => now()->subDay()->toDateString(), // expired yesterday
        'reward_type'    => 'recognition',
        'status'         => ReferralCampaignStatus::Active->value,
        'created_by'     => 1,
    ]);

    AlumniReferralCode::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'campaign_id'    => $expiredCampaign->id,
        'alumni_id'      => $this->alumni->id,
        'code'           => 'EXPRD001',
        'is_active'      => true,
        'reward_status'  => ReferralRewardStatus::Pending->value,
    ]);

    $lead = Lead::factory()->create(['institution_id' => $this->institution->id]);

    $this->service->trackReferral('EXPRD001', $lead);

    $lead->refresh();

    expect($lead->referred_by_alumni_id)->toBeNull();
    expect($lead->referral_code)->toBeNull();
});

it('lead submitted with an invalid ref code is created normally without referral tags', function (): void {
    $lead = Lead::factory()->create(['institution_id' => $this->institution->id]);

    $this->service->trackReferral('BADCODE9', $lead);

    $lead->refresh();

    expect($lead->referred_by_alumni_id)->toBeNull();
    expect($lead->referral_code)->toBeNull();
    expect($lead->referral_campaign_id)->toBeNull();
});

it('trackReferral() does not accept a ref code from a different institution', function (): void {
    // Code belongs to institution A
    $otherInstitution = Institution::factory()->create();
    $lead = Lead::factory()->create(['institution_id' => $otherInstitution->id]);

    // Try to use the code belonging to $this->institution
    $this->service->trackReferral('VALID001', $lead);

    $lead->refresh();

    expect($lead->referred_by_alumni_id)->toBeNull();
    expect($lead->referral_code)->toBeNull();
});
