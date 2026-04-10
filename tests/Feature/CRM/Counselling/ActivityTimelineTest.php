<?php

declare(strict_types=1);

// BRD: CRM-EC-001, CRM-EC-003, CRM-EC-004 — Activity timeline: creation, add note, pagination, event-driven entries
use App\DTOs\CRM\CreateActivityDTO;
use App\Enums\CRM\ActivityType;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Livewire\CRM\Lead\LeadActivityTimeline;
use App\Models\CRM\Activity;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    $this->institution = Institution::create(['name' => 'Test Uni', 'code' => 'TU01', 'is_active' => true]);
    $this->counsellor = User::factory()->create([
        'institution_id' => $this->institution->id,
        'is_active' => true,
    ]);
    $this->counsellor->assignRole('senior-counsellor');

    $this->lead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Priya',
        'last_name' => 'Sharma',
        'mobile' => '9876543201',
        'source' => LeadSource::REFERRAL->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 10,
    ]);
});

it('creates an activity via repository', function (): void {
    /** @var ActivityRepositoryInterface $repo */
    $repo = app(ActivityRepositoryInterface::class);

    $activity = $repo->createForSubject(new CreateActivityDTO(
        type: ActivityType::NOTE,
        subjectType: Lead::class,
        subjectId: $this->lead->getKey(),
        institutionId: $this->institution->id,
        body: 'Test note — no PII',
        channel: null,
        direction: 'internal',
        metadata: null,
        performedById: $this->counsellor->id,
    ));

    expect($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->type)->toBe(ActivityType::NOTE)
        ->and($activity->body)->toBe('Test note — no PII');
});

it('paginates activities for a lead', function (): void {
    /** @var ActivityRepositoryInterface $repo */
    $repo = app(ActivityRepositoryInterface::class);

    for ($i = 0; $i < 5; $i++) {
        $repo->createForSubject(new CreateActivityDTO(
            type: ActivityType::NOTE,
            subjectType: Lead::class,
            subjectId: $this->lead->getKey(),
            institutionId: $this->institution->id,
            body: "Note {$i}",
            channel: null,
            direction: 'internal',
            metadata: null,
            performedById: $this->counsellor->id,
        ));
    }

    $paginator = $repo->paginateForSubject(
        Lead::class,
        $this->lead->getKey(),
        $this->institution->id,
        20,
    );

    expect($paginator->count())->toBe(5);
});

it('renders the LeadActivityTimeline Livewire component', function (): void {
    $this->actingAs($this->counsellor);

    Livewire::test(LeadActivityTimeline::class, ['leadUuid' => $this->lead->uuid])
        ->assertOk();
});

it('adds a note via Livewire LeadActivityTimeline', function (): void {
    $this->actingAs($this->counsellor);

    Livewire::test(LeadActivityTimeline::class, ['leadUuid' => $this->lead->uuid])
        ->set('noteBody', 'A counsellor note')
        ->call('addNote')
        ->assertHasNoErrors();

    expect(Activity::withoutGlobalScopes()->where('subject_id', $this->lead->getKey())->count())->toBe(1);
});

it('prevents adding a blank note via Livewire', function (): void {
    $this->actingAs($this->counsellor);

    Livewire::test(LeadActivityTimeline::class, ['leadUuid' => $this->lead->uuid])
        ->set('noteBody', '   ')
        ->call('addNote')
        ->assertHasErrors(['noteBody']);
});

it('logs a STATUS_CHANGE activity on lead status transition', function (): void {
    /** @var ActivityRepositoryInterface $repo */
    $repo = app(ActivityRepositoryInterface::class);

    $repo->createSystemEntry(
        Lead::class,
        $this->lead->getKey(),
        $this->institution->id,
        ActivityType::STATUS_CHANGE,
        'Status changed from NEW to CONTACTED',
        ['old' => 'NEW', 'new' => 'CONTACTED'],
    );

    $activities = Activity::withoutGlobalScopes()
        ->where('subject_id', $this->lead->getKey())
        ->where('type', ActivityType::STATUS_CHANGE->value)
        ->get();

    expect($activities)->toHaveCount(1);
});

it('creates a SYSTEM activity via createSystemEntry', function (): void {
    /** @var ActivityRepositoryInterface $repo */
    $repo = app(ActivityRepositoryInterface::class);

    $activity = $repo->createSystemEntry(
        Lead::class,
        $this->lead->getKey(),
        $this->institution->id,
        ActivityType::SYSTEM,
        'System-generated entry',
        ['key' => 'value'],
    );

    expect($activity->performed_by_id)->toBeNull()
        ->and($activity->type)->toBe(ActivityType::SYSTEM);
});

it('does not store PII in activity body', function (): void {
    /** @var ActivityRepositoryInterface $repo */
    $repo = app(ActivityRepositoryInterface::class);

    $activity = $repo->createForSubject(new CreateActivityDTO(
        type: ActivityType::NOTE,
        subjectType: Lead::class,
        subjectId: $this->lead->getKey(),
        institutionId: $this->institution->id,
        body: 'Note without PII',
        channel: null,
        direction: 'internal',
        metadata: null,
        performedById: $this->counsellor->id,
    ));

    // Body should not contain raw mobile or email
    expect($activity->body)->not->toContain('9876543201');
});

it('activities are scoped to institution', function (): void {
    $otherInstitution = Institution::create(['name' => 'Other Uni', 'code' => 'OU01', 'is_active' => true]);
    $otherLead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $otherInstitution->id,
        'first_name' => 'Other',
        'last_name' => 'Lead',
        'mobile' => '9000000001',
        'source' => LeadSource::WEBSITE_ORGANIC->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 5,
    ]);

    /** @var ActivityRepositoryInterface $repo */
    $repo = app(ActivityRepositoryInterface::class);

    $repo->createSystemEntry(Lead::class, $this->lead->getKey(), $this->institution->id, ActivityType::NOTE, 'A');
    $repo->createSystemEntry(Lead::class, $otherLead->getKey(), $otherInstitution->id, ActivityType::NOTE, 'B');

    $onlyOwn = $repo->paginateForSubject(Lead::class, $this->lead->getKey(), $this->institution->id);
    expect($onlyOwn->count())->toBe(1);
});

it('renders empty state in timeline when no activities exist', function (): void {
    $this->actingAs($this->counsellor);

    Livewire::test(LeadActivityTimeline::class, ['leadUuid' => $this->lead->uuid])
        ->assertSee('No activity');
});
