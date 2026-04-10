<?php

declare(strict_types=1);

// BRD: CRM-LC-018 — Auto-detect duplicate leads on mobile/email match and name+course combination

use App\Events\CRM\DuplicateLeadFlaggedEvent;
use App\Jobs\CRM\DetectLeadDuplicatesJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Lead\LeadService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// Seed permissions before every test in this file
beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

// ─── Helpers ───────────────────────────────────────────────────────────────

function makeInstitutionAndCounsellorForDedup(): array
{
    $institution = Institution::create([
        'name' => 'Dedup University', 'code' => 'DU01', 'is_active' => true,
    ]);

    $counsellor = User::create([
        'name' => 'Dedup Counsellor',
        'email' => 'dedup@du.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $counsellor->givePermissionTo([
        'crm.leads.create',
        'crm.leads.view',
        'crm.leads.edit',
        'crm.leads.view_pii',
    ]);

    return [$institution, $counsellor];
}

function makeLeadWithoutGlobalScope(int $institutionId, array $overrides = []): Lead
{
    return Lead::withoutGlobalScopes()->create(array_merge([
        'institution_id' => $institutionId,
        'first_name' => 'Priya',
        'last_name' => 'Verma',
        'mobile' => '9876543210',
        'email' => 'priya@test.com',
        'source' => 'walk_in',
        'lead_score' => 0,
        'temperature' => 'cold',
        'status' => 'new_enquiry',
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1.0',
    ], $overrides));
}

// ─── CRM-LC-018: duplicate detection job dispatched on creation ─────────────

test('DetectLeadDuplicatesJob is dispatched after lead creation via API (CRM-LC-018)', function (): void {
    Queue::fake();

    [$institution, $counsellor] = makeInstitutionAndCounsellorForDedup();

    $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', [
            'first_name' => 'Arjun',
            'last_name' => 'Sharma',
            'mobile' => '9123456780',
            'source' => 'walk_in',
            'consent_given' => true,
            'consent_form_version' => 'v1.0',
        ])
        ->assertStatus(201);

    Queue::assertPushed(DetectLeadDuplicatesJob::class, function (DetectLeadDuplicatesJob $job) use ($institution): bool {
        return $job->institutionId === $institution->id;
    });
});

// ─── CRM-LC-018: mobile match → is_duplicate_suspected = true ───────────────

test('DetectLeadDuplicatesJob flags lead when same mobile exists (CRM-LC-018)', function (): void {
    Event::fake([DuplicateLeadFlaggedEvent::class]);

    [$institution] = makeInstitutionAndCounsellorForDedup();

    // Original lead
    makeLeadWithoutGlobalScope($institution->id, ['mobile' => '9876543210', 'email' => 'original@test.com']);

    // New lead with same mobile
    $newLead = makeLeadWithoutGlobalScope($institution->id, [
        'mobile' => '9876543210',
        'email' => 'different@test.com',
        'first_name' => 'New',
        'last_name' => 'Person',
    ]);

    (new DetectLeadDuplicatesJob($newLead->uuid, $institution->id))->handle();

    $newLead->refresh();
    expect($newLead->is_duplicate_suspected)->toBeTrue();
    expect($newLead->duplicate_of_uuid)->not->toBeNull();
});

// ─── CRM-LC-018: email match → is_duplicate_suspected = true ────────────────

test('DetectLeadDuplicatesJob flags lead when same email exists (CRM-LC-018)', function (): void {
    Event::fake([DuplicateLeadFlaggedEvent::class]);

    [$institution] = makeInstitutionAndCounsellorForDedup();

    makeLeadWithoutGlobalScope($institution->id, ['mobile' => '9111111111', 'email' => 'shared@test.com']);

    $newLead = makeLeadWithoutGlobalScope($institution->id, [
        'mobile' => '9222222222',
        'email' => 'shared@test.com',
    ]);

    (new DetectLeadDuplicatesJob($newLead->uuid, $institution->id))->handle();

    $newLead->refresh();
    expect($newLead->is_duplicate_suspected)->toBeTrue();
});

// ─── CRM-LC-018: DuplicateLeadFlaggedEvent is fired ─────────────────────────

test('DuplicateLeadFlaggedEvent is dispatched with correct match type (CRM-LC-018)', function (): void {
    Event::fake([DuplicateLeadFlaggedEvent::class]);

    [$institution] = makeInstitutionAndCounsellorForDedup();

    makeLeadWithoutGlobalScope($institution->id, ['mobile' => '9876543210']);

    $newLead = makeLeadWithoutGlobalScope($institution->id, [
        'mobile' => '9876543210',
        'first_name' => 'Different',
    ]);

    (new DetectLeadDuplicatesJob($newLead->uuid, $institution->id))->handle();

    Event::assertDispatched(DuplicateLeadFlaggedEvent::class, function (DuplicateLeadFlaggedEvent $event): bool {
        return $event->matchType === 'mobile_email'
            && $event->duplicates->isNotEmpty();
    });
});

// ─── CRM-LC-018: no false-positive across institutions ───────────────────────

test('DetectLeadDuplicatesJob does NOT flag cross-institution mobile match (CRM-LC-018)', function (): void {
    Event::fake([DuplicateLeadFlaggedEvent::class]);

    $instA = Institution::create(['name' => 'Inst A', 'code' => 'IA01', 'is_active' => true]);
    $instB = Institution::create(['name' => 'Inst B', 'code' => 'IB01', 'is_active' => true]);

    // Same mobile, different institutions
    makeLeadWithoutGlobalScope($instA->id, ['mobile' => '9876543210']);
    $leadB = makeLeadWithoutGlobalScope($instB->id, ['mobile' => '9876543210']);

    (new DetectLeadDuplicatesJob($leadB->uuid, $instB->id))->handle();

    $leadB->refresh();
    expect($leadB->is_duplicate_suspected)->toBeFalse();

    Event::assertNotDispatched(DuplicateLeadFlaggedEvent::class);
});

// ─── CRM-LC-018: no flag when no duplicates exist ────────────────────────────

test('DetectLeadDuplicatesJob does not flag unique lead (CRM-LC-018)', function (): void {
    Event::fake([DuplicateLeadFlaggedEvent::class]);

    [$institution] = makeInstitutionAndCounsellorForDedup();

    $lead = makeLeadWithoutGlobalScope($institution->id, [
        'mobile' => '9000000001',
        'email' => 'unique@test.com',
    ]);

    (new DetectLeadDuplicatesJob($lead->uuid, $institution->id))->handle();

    $lead->refresh();
    expect($lead->is_duplicate_suspected)->toBeFalse();

    Event::assertNotDispatched(DuplicateLeadFlaggedEvent::class);
});

// ─── CRM-LC-018: re-detection triggered on contact-info update ───────────────

test('LeadService dispatches DetectLeadDuplicatesJob when mobile is updated (CRM-LC-018)', function (): void {
    Queue::fake();

    [$institution] = makeInstitutionAndCounsellorForDedup();

    $lead = makeLeadWithoutGlobalScope($institution->id, [
        'mobile' => '9100000001',
        'email' => 'ravi@test.com',
    ]);

    // Call the service directly — mobile is not updatable via the public API
    // (intentional: mobile is a primary identifier, but service allows it for counsellor-verified updates)
    $service = app(LeadService::class);
    $service->update($lead, ['mobile' => '9100000002']);

    Queue::assertPushed(DetectLeadDuplicatesJob::class, function (DetectLeadDuplicatesJob $job) use ($lead): bool {
        return $job->leadUuid === $lead->uuid;
    });
});

test('LeadService dispatches DetectLeadDuplicatesJob when email is updated (CRM-LC-018)', function (): void {
    Queue::fake();

    [$institution] = makeInstitutionAndCounsellorForDedup();

    $lead = makeLeadWithoutGlobalScope($institution->id, [
        'mobile' => '9100000003',
        'email' => 'original@test.com',
    ]);

    $service = app(LeadService::class);
    $service->update($lead, ['email' => 'updated@test.com']);

    Queue::assertPushed(DetectLeadDuplicatesJob::class, function (DetectLeadDuplicatesJob $job) use ($lead): bool {
        return $job->leadUuid === $lead->uuid;
    });
});

test('LeadService does NOT dispatch DetectLeadDuplicatesJob on non-contact update (CRM-LC-018)', function (): void {
    Queue::fake();

    [$institution] = makeInstitutionAndCounsellorForDedup();

    $lead = makeLeadWithoutGlobalScope($institution->id);

    $service = app(LeadService::class);
    $service->update($lead, ['notes' => 'Updated notes only']);

    Queue::assertNotPushed(DetectLeadDuplicatesJob::class);
});
