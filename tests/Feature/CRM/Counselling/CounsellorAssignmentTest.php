<?php

declare(strict_types=1);

// BRD: CRM-EC-006, CRM-EC-007 — Counsellor auto-assignment and manual reassignment
use App\Enums\CRM\AssignmentMode;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Events\CRM\LeadAssignedEvent;
use App\Models\CRM\CounsellorAssignmentConfig;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Counselling\CounsellorAssignmentService;
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

    $this->counsellor = User::factory()->create([
        'institution_id' => $this->institution->id,
        'is_active' => true,
    ]);
    $this->counsellor->assignRole('senior-counsellor');

    $this->admin = User::factory()->create([
        'institution_id' => $this->institution->id,
        'is_active' => true,
    ]);
    $this->admin->assignRole('institution-admin');

    $this->lead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'mobile' => '9876543210',
        'source' => LeadSource::REFERRAL->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 20,
    ]);

    $this->svc = app(CounsellorAssignmentService::class);
});

it('auto-assigns a lead via round-robin', function (): void {
    Event::fake([LeadAssignedEvent::class]);

    CounsellorAssignmentConfig::withoutGlobalScopes()->updateOrCreate(
        ['institution_id' => $this->institution->id],
        ['assignment_mode' => AssignmentMode::ROUND_ROBIN->value, 'round_robin_pointer' => 0, 'escalation_hours' => 24],
    );

    $assignedId = $this->svc->autoAssign($this->lead);

    expect($assignedId)->toBe($this->counsellor->id);
    Event::assertDispatched(LeadAssignedEvent::class);
});

it('returns null when mode is MANUAL', function (): void {
    CounsellorAssignmentConfig::withoutGlobalScopes()->updateOrCreate(
        ['institution_id' => $this->institution->id],
        ['assignment_mode' => AssignmentMode::MANUAL->value, 'round_robin_pointer' => 0, 'escalation_hours' => 24],
    );

    $result = $this->svc->autoAssign($this->lead);

    expect($result)->toBeNull();
});

it('manually assigns a lead and fires LeadAssignedEvent', function (): void {
    Event::fake([LeadAssignedEvent::class]);

    $updated = $this->svc->manualAssign($this->lead, $this->counsellor->id, $this->admin->id);

    expect($updated->assigned_counsellor_id)->toBe($this->counsellor->id);
    Event::assertDispatched(LeadAssignedEvent::class);
});

it('reassigns a lead from one counsellor to another', function (): void {
    Event::fake([LeadAssignedEvent::class]);

    $newCounsellor = User::factory()->create(['institution_id' => $this->institution->id, 'is_active' => true]);
    $newCounsellor->assignRole('junior-counsellor');

    $this->svc->manualAssign($this->lead, $this->counsellor->id, $this->admin->id);
    $updated = $this->svc->manualAssign($this->lead->fresh(), $newCounsellor->id, $this->admin->id);

    expect($updated->assigned_counsellor_id)->toBe($newCounsellor->id);
    Event::assertDispatchedTimes(LeadAssignedEvent::class, 2);
});

it('admin can post assign endpoint', function (): void {
    $this->actingAs($this->admin)
        ->post(route('crm.leads.assign', $this->lead), [
            'counsellor_id' => $this->counsellor->id,
            'reason' => 'Testing assignment',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($this->lead->fresh()->assigned_counsellor_id)->toBe($this->counsellor->id);
});

it('non-admin cannot assign lead', function (): void {
    $junior = User::factory()->create(['institution_id' => $this->institution->id, 'is_active' => true]);
    $junior->assignRole('junior-counsellor');

    $this->actingAs($junior)
        ->post(route('crm.leads.assign', $this->lead), [
            'counsellor_id' => $this->counsellor->id,
        ])
        ->assertForbidden();
});

it('getAvailableCounsellors returns only active counsellors', function (): void {
    $inactive = User::factory()->create(['institution_id' => $this->institution->id, 'is_active' => false]);
    $inactive->assignRole('senior-counsellor');

    CounsellorAssignmentConfig::withoutGlobalScopes()->updateOrCreate(
        ['institution_id' => $this->institution->id],
        ['assignment_mode' => AssignmentMode::ROUND_ROBIN->value, 'round_robin_pointer' => 0, 'escalation_hours' => 24],
    );

    $assignedId = $this->svc->autoAssign($this->lead);
    expect($assignedId)->toBe($this->counsellor->id); // only the active one
});

it('admissions-manager can view assignment config page', function (): void {
    $manager = User::factory()->create(['institution_id' => $this->institution->id, 'is_active' => true]);
    $manager->assignRole('institution-admin'); // institution-admin has crm.settings.manage

    $this->actingAs($manager)
        ->get(route('crm.settings.assignment-config'))
        ->assertOk();
});

it('senior counsellor cannot view assignment config page', function (): void {
    $this->actingAs($this->counsellor)
        ->get(route('crm.settings.assignment-config'))
        ->assertForbidden();
});

it('workload dashboard renders for admin', function (): void {
    $this->actingAs($this->admin)
        ->get(route('crm.counsellors.workload'))
        ->assertOk();
});

it('load-balanced mode assigns to counsellor with fewest leads', function (): void {
    Event::fake([LeadAssignedEvent::class]);

    $counsellor2 = User::factory()->create(['institution_id' => $this->institution->id, 'is_active' => true]);
    $counsellor2->assignRole('junior-counsellor');

    // Give counsellor1 one lead already
    Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Existing',
        'last_name' => 'Lead',
        'mobile' => '9000000002',
        'source' => LeadSource::WEBSITE_ORGANIC->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 5,
        'assigned_counsellor_id' => $this->counsellor->id,
    ]);

    CounsellorAssignmentConfig::withoutGlobalScopes()->updateOrCreate(
        ['institution_id' => $this->institution->id],
        ['assignment_mode' => AssignmentMode::LOAD_BALANCED->value, 'round_robin_pointer' => 0, 'escalation_hours' => 24],
    );

    $assignedId = $this->svc->autoAssign($this->lead);
    expect($assignedId)->toBe($counsellor2->id); // counsellor2 has 0 leads
});
