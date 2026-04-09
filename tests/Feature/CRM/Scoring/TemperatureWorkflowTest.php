<?php

declare(strict_types=1);

// BRD: CRM-LQ-006 — Automated workflow triggered on temperature change
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Jobs\CRM\QueueNurtureSequenceJob;
use App\Jobs\CRM\SendHotLeadAlertJob;
use App\Listeners\CRM\TriggerScoringWorkflowListener;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\PermissionSeeder::class);
    $this->institution = Institution::create(['name' => 'Workflow Uni', 'code' => 'WFU', 'is_active' => true]);

    $this->counsellor = User::create([
        'name' => 'Alert Counsellor', 'email' => 'alert@wfu.com',
        'password' => bcrypt('pass'), 'institution_id' => $this->institution->id,
    ]);

    $this->lead = Lead::withoutGlobalScopes()->create([
        'uuid'                     => \Illuminate\Support\Str::uuid(),
        'institution_id'           => $this->institution->id,
        'first_name'               => 'Workflow',
        'last_name'                => 'Lead',
        'mobile'                   => '8888888888',
        'source'                   => LeadSource::REFERRAL->value,
        'status'                   => LeadStatus::NEW_ENQUIRY->value,
        'consent_given'            => true,
        'lead_score'               => 40,
        'temperature'              => LeadTemperature::WARM->value,
        'assigned_counsellor_id'   => $this->counsellor->id,
        'score_manually_overridden' => false,
    ]);
});

it('dispatches SendHotLeadAlertJob when lead becomes HOT', function (): void {
    Bus::fake();

    $event    = new LeadTemperatureChangedEvent($this->lead, LeadTemperature::WARM, LeadTemperature::HOT);
    $listener = new TriggerScoringWorkflowListener();

    $listener->handle($event);

    Bus::assertDispatched(SendHotLeadAlertJob::class, fn ($job) => $job->leadUuid === (string) $this->lead->uuid);
});

it('dispatches QueueNurtureSequenceJob when lead downgrades to COLD from WARM', function (): void {
    Bus::fake();

    $event    = new LeadTemperatureChangedEvent($this->lead, LeadTemperature::WARM, LeadTemperature::COLD);
    $listener = new TriggerScoringWorkflowListener();

    $listener->handle($event);

    Bus::assertDispatched(QueueNurtureSequenceJob::class, fn ($job) => $job->leadUuid === (string) $this->lead->uuid);
});

it('does not dispatch any job when temperature changes to WARM', function (): void {
    Bus::fake();

    $event    = new LeadTemperatureChangedEvent($this->lead, LeadTemperature::COLD, LeadTemperature::WARM);
    $listener = new TriggerScoringWorkflowListener();

    $listener->handle($event);

    Bus::assertNothingDispatched();
});

it('does not dispatch nurture job when initial state is already COLD (not a downgrade)', function (): void {
    Bus::fake();

    // COLD → COLD transition (should never happen, but guard test)
    $event    = new LeadTemperatureChangedEvent($this->lead, LeadTemperature::COLD, LeadTemperature::COLD);
    $listener = new TriggerScoringWorkflowListener();

    $listener->handle($event);

    Bus::assertNotDispatched(QueueNurtureSequenceJob::class);
});

it('sends notification to assigned counsellor via database and mail channels when HOT', function (): void {
    Notification::fake();

    SendHotLeadAlertJob::dispatchSync((string) $this->lead->uuid);

    Notification::assertSentTo(
        $this->counsellor,
        \App\Notifications\CRM\HotLeadAlertNotification::class,
        fn ($notification) => (string) $notification->lead->uuid === (string) $this->lead->uuid,
    );
});
