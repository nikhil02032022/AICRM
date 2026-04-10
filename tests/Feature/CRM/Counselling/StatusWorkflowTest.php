<?php

declare(strict_types=1);

// BRD: CRM-EC-011, CRM-EC-012, CRM-EC-013, CRM-EC-014 — Status workflow, lost reason, status_changed_at
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LostReason;
use App\Events\CRM\LeadStatusChangedEvent;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Counselling\LeadStatusWorkflowService;
use App\Services\CRM\Lead\LeadService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    $this->institution = Institution::create(['name' => 'Test Uni', 'code' => 'TU01', 'is_active' => true]);
    $this->lead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Test',
        'last_name' => 'User',
        'mobile' => '9876543210',
        'source' => LeadSource::REFERRAL->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 20,
    ]);
    $this->svc = app(LeadService::class);
});

it('transitions lead status and fires LeadStatusChangedEvent', function (): void {
    Event::fake([LeadStatusChangedEvent::class]);

    $this->svc->transitionStatus($this->lead, LeadStatus::CONTACTED);

    $this->lead->refresh();
    expect($this->lead->status)->toBe(LeadStatus::CONTACTED);

    Event::assertDispatched(LeadStatusChangedEvent::class);
});

it('updates status_changed_at on transition', function (): void {
    $before = now()->subSecond();

    $this->svc->transitionStatus($this->lead, LeadStatus::CONTACTED);
    $this->lead->refresh();

    expect($this->lead->status_changed_at)->toBeGreaterThan($before);
});

it('requires lost_reason when transitioning to LOST', function (): void {
    expect(fn () => $this->svc->transitionStatus($this->lead, LeadStatus::LOST))
        ->toThrow(InvalidArgumentException::class);
});

it('allows LOST transition when lost_reason is provided', function (): void {
    Event::fake([LeadStatusChangedEvent::class]);

    $this->svc->transitionStatus($this->lead, LeadStatus::LOST, LostReason::NOT_INTERESTED);
    $this->lead->refresh();

    expect($this->lead->status)->toBe(LeadStatus::LOST)
        ->and($this->lead->lost_reason)->toBe(LostReason::NOT_INTERESTED);
});

it('lost_reason dropdown renders on modal when status is lost', function (): void {
    $admin = User::factory()->create(['institution_id' => $this->institution->id, 'is_active' => true]);
    $admin->assignRole('institution-admin');

    $this->actingAs($admin)
        ->get(route('crm.leads.show', $this->lead))
        ->assertOk()
        ->assertSee('lost_reason');
});

it('LostReason enum returns human-readable labels', function (): void {
    expect(LostReason::NOT_INTERESTED->label())->toBe('Not Interested')
        ->and(LostReason::JOINED_COMPETITOR->label())->toBe('Joined Competitor');
});

it('LostReason::optionsForSelect returns all cases', function (): void {
    $options = LostReason::optionsForSelect();
    expect($options)->toHaveCount(count(LostReason::cases()));
});

it('does not allow skipping status_changed_at update', function (): void {
    Event::fake([LeadStatusChangedEvent::class]);

    $this->svc->transitionStatus($this->lead, LeadStatus::CONTACTED);
    $this->lead->refresh();

    expect($this->lead->status_changed_at)->not->toBeNull();
});

it('LeadStatusWorkflowService handles COUNSELLING_SCHEDULED without throwing', function (): void {
    $wfs = app(LeadStatusWorkflowService::class);
    Event::fake([LeadStatusChangedEvent::class]);

    // Need to be in CONTACTED state first before COUNSELLING_SCHEDULED
    $this->svc->transitionStatus($this->lead, LeadStatus::CONTACTED);
    $this->lead->refresh();

    expect(fn () => $wfs->handleStatusChange($this->lead, LeadStatus::COUNSELLING_SCHEDULED))
        ->not->toThrow(Throwable::class);
});
