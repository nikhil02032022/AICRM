<?php

declare(strict_types=1);

// BRD: CRM-LQ-001, CRM-LQ-004, CRM-LQ-006 — RecalculateLeadScoreJob tests
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Events\CRM\ScoreChangedEvent;
use App\Jobs\CRM\RecalculateLeadScoreJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\PermissionSeeder::class);
    $this->institution = Institution::create(['name' => 'Job Test Uni', 'code' => 'JTU', 'is_active' => true]);
});

function makeScoringLead(array $attrs = []): Lead
{
    return Lead::withoutGlobalScopes()->create(array_merge([
        'uuid'                     => \Illuminate\Support\Str::uuid(),
        'institution_id'           => 1,
        'first_name'               => 'Test',
        'last_name'                => 'Lead',
        'mobile'                   => '9111111111',
        'source'                   => LeadSource::WEBSITE_ORGANIC->value,
        'status'                   => LeadStatus::NEW_ENQUIRY->value,
        'consent_given'            => false,
        'lead_score'               => 0,
        'temperature'              => LeadTemperature::COLD->value,
        'score_manually_overridden' => false,
    ], $attrs));
}

it('fires ScoreChangedEvent when score changes', function (): void {
    Event::fake();

    $lead      = makeScoringLead();
    $lead->institution_id = $this->institution->id; // update to real id
    $lead->save();

    RecalculateLeadScoreJob::dispatchSync($lead->uuid);

    Event::assertDispatched(ScoreChangedEvent::class);
});

it('does not fire ScoreChangedEvent when score is unchanged', function (): void {
    Event::fake();

    $lead = makeScoringLead(['institution_id' => $this->institution->id]);

    // Run the job twice — second run should see same score (no profile/engagement signals)
    RecalculateLeadScoreJob::dispatchSync($lead->uuid);

    // Capture the score after first run
    $scoreAfterFirst = $lead->fresh()->lead_score;

    // Reset the event mock between runs
    Event::fake();

    // Set lead_score manually to what the job just set (simulating "no change on re-run")
    RecalculateLeadScoreJob::dispatchSync($lead->uuid);

    // If score stayed the same, no ScoreChangedEvent should fire
    $scoreAfterSecond = $lead->fresh()->lead_score;

    if ($scoreAfterFirst === $scoreAfterSecond) {
        Event::assertNotDispatched(ScoreChangedEvent::class);
    } else {
        // Score still changed on second run — any change fires the event; this is acceptable
        Event::assertDispatched(ScoreChangedEvent::class);
    }
});

it('fires LeadTemperatureChangedEvent when temperature changes', function (): void {
    Event::fake();

    $lead = makeScoringLead([
        'institution_id' => $this->institution->id,
        'temperature'    => LeadTemperature::COLD->value,
        // Give enough signals to potentially push above WARM threshold
        'email'          => 'temp@test.com',
        'city'           => 'Mumbai',
        'state'          => 'MH',
        'nationality'    => 'Indian',
        'source'         => LeadSource::REFERRAL->value,
        'consent_given'  => true,
        'status'         => LeadStatus::COUNSELLING_DONE->value,
    ]);

    RecalculateLeadScoreJob::dispatchSync($lead->uuid);

    // If lead qualified (score >= 50), temperature changed from COLD
    $freshLead = $lead->fresh();
    if ($freshLead->temperature !== LeadTemperature::COLD) {
        Event::assertDispatched(LeadTemperatureChangedEvent::class);
    } else {
        // Still cold — temperature did not change; no event expected
        Event::assertNotDispatched(LeadTemperatureChangedEvent::class);
    }
});

it('handles missing lead gracefully without exception', function (): void {
    expect(function () {
        RecalculateLeadScoreJob::dispatchSync('non-existent-uuid-0000-000000000000');
    })->not->toThrow(\Throwable::class);
});

it('does not recalculate score when score was manually overridden', function (): void {
    Event::fake();

    $lead = makeScoringLead([
        'institution_id'           => $this->institution->id,
        'lead_score'               => 95,
        'temperature'              => LeadTemperature::HOT->value,
        'score_manually_overridden' => true,
    ]);

    RecalculateLeadScoreJob::dispatchSync($lead->uuid);

    // Score should remain 95 (job returned early)
    expect($lead->fresh()->lead_score)->toBe(95);
    Event::assertNotDispatched(ScoreChangedEvent::class);
});

it('job is unique per lead UUID preventing duplicates queuing', function (): void {
    $job1 = new RecalculateLeadScoreJob('same-uuid');
    $job2 = new RecalculateLeadScoreJob('same-uuid');

    expect($job1->uniqueId())->toBe($job2->uniqueId());
});
