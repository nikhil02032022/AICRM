<?php

declare(strict_types=1);

// BRD: CRM-LQ-001, CRM-LQ-002, CRM-LQ-005, CRM-LQ-007, CRM-LQ-008 — LeadScoringService unit tests
use App\DTOs\CRM\ScoreOverrideDTO;
use App\DTOs\CRM\UpdateScoringConfigDTO;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\Institution;
use App\Models\CRM\InstitutionScoringConfig;
use App\Models\CRM\Lead;
use App\Models\CRM\ScoreOverride;
use App\Models\User;
use App\Services\CRM\Scoring\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\PermissionSeeder::class);
    $this->institution = Institution::create(['name' => 'Test Uni', 'code' => 'TU01', 'is_active' => true]);
    $this->service     = app(LeadScoringService::class);
});

it('calculates score with all signals present', function (): void {
    $lead = Lead::withoutGlobalScopes()->create([
        'uuid'               => \Illuminate\Support\Str::uuid(),
        'institution_id'     => $this->institution->id,
        'first_name'         => 'Arjun',
        'last_name'          => 'Sharma',
        'mobile'             => '9876543210',
        'email'              => 'arjun@example.com',
        'city'               => 'Mumbai',
        'state'              => 'Maharashtra',
        'nationality'        => 'Indian',
        'source'             => LeadSource::REFERRAL->value,
        'status'             => LeadStatus::COUNSELLING_DONE->value,
        'consent_given'      => true,
        'lead_score'         => 0,
        'temperature'        => LeadTemperature::COLD->value,
        'score_manually_overridden' => false,
    ]);

    $config = $this->service->getScoringConfig($this->institution->id);
    $score  = $this->service->calculateScore($lead, $config);

    expect($score)->toBeGreaterThan(50)->toBeLessThanOrEqual(100);
});

it('loads institution scoring config and creates default if missing', function (): void {
    $config = $this->service->getScoringConfig($this->institution->id);

    expect($config)->toBeInstanceOf(InstitutionScoringConfig::class)
        ->and($config->hot_threshold)->toBe(75)
        ->and($config->warm_threshold)->toBe(50)
        ->and($config->weights)->toHaveKey('profile_completeness');
});

it('does not create duplicate default configs', function (): void {
    $this->service->getScoringConfig($this->institution->id);
    $this->service->getScoringConfig($this->institution->id);

    $count = InstitutionScoringConfig::withoutGlobalScopes()
        ->where('institution_id', $this->institution->id)
        ->count();

    expect($count)->toBe(1);
});

it('applies referral source quality weight correctly', function (): void {
    $lead = Lead::withoutGlobalScopes()->create([
        'uuid'               => \Illuminate\Support\Str::uuid(),
        'institution_id'     => $this->institution->id,
        'first_name'         => 'Test',
        'last_name'          => 'User',
        'mobile'             => '9000000001',
        'source'             => LeadSource::REFERRAL->value,
        'status'             => LeadStatus::NEW_ENQUIRY->value,
        'consent_given'      => false,
        'lead_score'         => 0,
        'temperature'        => LeadTemperature::COLD->value,
        'score_manually_overridden' => false,
    ]);

    $config          = $this->service->getScoringConfig($this->institution->id);
    $referralScore   = $this->service->calculateScore($lead, $config);

    // Replace source with a lower quality channel
    $lead->source = LeadSource::CSV_IMPORT;
    $lowTierScore  = $this->service->calculateScore($lead, $config);

    expect($referralScore)->toBeGreaterThan($lowTierScore);
});

it('scores engagement when status has advanced beyond new enquiry', function (): void {
    Event::fake();

    $leadAdvanced = Lead::withoutGlobalScopes()->create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name'     => 'Advanced',
        'last_name'      => 'Lead',
        'mobile'         => '9000000002',
        'source'         => LeadSource::WEBSITE_ORGANIC->value,
        'status'         => LeadStatus::COUNSELLING_DONE->value,
        'consent_given'  => false,
        'lead_score'     => 0,
        'temperature'    => LeadTemperature::COLD->value,
        'score_manually_overridden' => false,
    ]);

    $leadNew = Lead::withoutGlobalScopes()->create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name'     => 'New',
        'last_name'      => 'Lead',
        'mobile'         => '9000000003',
        'source'         => LeadSource::WEBSITE_ORGANIC->value,
        'status'         => LeadStatus::NEW_ENQUIRY->value,
        'consent_given'  => false,
        'lead_score'     => 0,
        'temperature'    => LeadTemperature::COLD->value,
        'score_manually_overridden' => false,
    ]);

    $config       = $this->service->getScoringConfig($this->institution->id);
    $advancedScore = $this->service->calculateScore($leadAdvanced, $config);
    $newScore      = $this->service->calculateScore($leadNew, $config);

    expect($advancedScore)->toBeGreaterThan($newScore);
});

it('caps score at 100 even if signals sum exceeds it', function (): void {
    // Configure all weights to max possible values
    $config = $this->service->getScoringConfig($this->institution->id);
    $config->weights = [
        'profile_completeness' => 30,
        'programme_interest'   => 30,
        'source_quality'       => 30,
        'engagement'           => 30,
        'consent'              => 10,
        'geographic'           => 10,
        'response_time'        => 10,
    ];
    $config->save();

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name'     => 'Full',
        'last_name'      => 'Lead',
        'mobile'         => '9000000004',
        'email'          => 'full@example.com',
        'city'           => 'Delhi',
        'state'          => 'Delhi',
        'nationality'    => 'Indian',
        'source'         => LeadSource::REFERRAL->value,
        'status'         => LeadStatus::COUNSELLING_DONE->value,
        'consent_given'  => true,
        'lead_score'     => 0,
        'temperature'    => LeadTemperature::COLD->value,
        'score_manually_overridden' => false,
    ]);

    $score = $this->service->calculateScore($lead, $config->fresh());

    expect($score)->toBeLessThanOrEqual(100);
});

it('applies institution-configurable thresholds correctly', function (): void {
    // Set very high thresholds
    $config = $this->service->getScoringConfig($this->institution->id);
    $config->hot_threshold  = 90;
    $config->warm_threshold = 70;
    $config->save();

    expect($this->service->deriveTemperature(85, $config->fresh()))->toBe(LeadTemperature::WARM)
        ->and($this->service->deriveTemperature(92, $config->fresh()))->toBe(LeadTemperature::HOT)
        ->and($this->service->deriveTemperature(60, $config->fresh()))->toBe(LeadTemperature::COLD);
});

it('stores score override audit record and updates lead score', function (): void {
    Event::fake();

    $user = User::create([
        'name' => 'Counsellor', 'email' => 'c@test.com',
        'password' => bcrypt('x'), 'institution_id' => $this->institution->id,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name'     => 'Override',
        'last_name'      => 'Lead',
        'mobile'         => '9000000005',
        'source'         => LeadSource::WEBSITE_ORGANIC->value,
        'status'         => LeadStatus::NEW_ENQUIRY->value,
        'consent_given'  => false,
        'lead_score'     => 40,
        'temperature'    => LeadTemperature::COLD->value,
        'score_manually_overridden' => false,
    ]);

    $dto = new ScoreOverrideDTO(
        leadUuid: (string) $lead->uuid, overriddenScore: 80, reason: 'Counsellor spoke to lead — very interested', actorId: $user->id,
    );

    $override = $this->service->applyManualOverride($lead, $dto);

    expect($override)->toBeInstanceOf(ScoreOverride::class)
        ->and($override->previous_score)->toBe(40)
        ->and($override->overridden_score)->toBe(80)
        ->and($lead->fresh()->lead_score)->toBe(80)
        ->and($lead->fresh()->score_manually_overridden)->toBeTrue();
});

it('fires ScoreChangedEvent and LeadTemperatureChangedEvent on override', function (): void {
    Event::fake();

    $user = User::create([
        'name' => 'Counsellor2', 'email' => 'c2@test.com',
        'password' => bcrypt('x'), 'institution_id' => $this->institution->id,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name'     => 'Event',
        'last_name'      => 'Test',
        'mobile'         => '9000000006',
        'source'         => LeadSource::WEBSITE_ORGANIC->value,
        'status'         => LeadStatus::NEW_ENQUIRY->value,
        'consent_given'  => false,
        'lead_score'     => 30,
        'temperature'    => LeadTemperature::COLD->value,
        'score_manually_overridden' => false,
    ]);

    $dto = new ScoreOverrideDTO(leadUuid: (string) $lead->uuid, overriddenScore: 80, reason: 'Manually upgraded — spoke directly', actorId: $user->id);

    $this->service->applyManualOverride($lead, $dto);

    Event::assertDispatched(\App\Events\CRM\ScoreChangedEvent::class);
    Event::assertDispatched(\App\Events\CRM\LeadTemperatureChangedEvent::class);
});

it('persists updated scoring config weights', function (): void {
    $dto = new UpdateScoringConfigDTO(
        weights: [
            'profile_completeness' => 15,
            'programme_interest'   => 25,
            'source_quality'       => 25,
            'engagement'           => 20,
            'consent'              => 5,
            'geographic'           => 5,
            'response_time'        => 5,
        ],
        hotThreshold: 80,
        warmThreshold: 55,
    );

    $config = $this->service->updateConfig($this->institution->id, $dto);

    expect($config->hot_threshold)->toBe(80)
        ->and($config->warm_threshold)->toBe(55)
        ->and($config->weights['programme_interest'])->toBe(25);
});

it('getSourceQualityReport returns correct grouping', function (): void {
    // Create leads with known sources
    foreach ([LeadSource::REFERRAL, LeadSource::REFERRAL, LeadSource::GOOGLE_ADS] as $i => $source) {
        Lead::withoutGlobalScopes()->create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'institution_id' => $this->institution->id,
            'first_name'     => "Lead{$i}",
            'last_name'      => 'Test',
            'mobile'         => "90000000{$i}0",
            'source'         => $source->value,
            'status'         => LeadStatus::NEW_ENQUIRY->value,
            'consent_given'  => false,
            'lead_score'     => ($i + 1) * 20,
            'temperature'    => LeadTemperature::COLD->value,
            'score_manually_overridden' => false,
        ]);
    }

    $report = $this->service->getSourceQualityReport($this->institution->id);

    $sources = $report->pluck('source')->toArray();

    expect($report)->toHaveCount(2)
        ->and($sources)->toContain(LeadSource::REFERRAL->value)
        ->and($sources)->toContain(LeadSource::GOOGLE_ADS->value);
});
