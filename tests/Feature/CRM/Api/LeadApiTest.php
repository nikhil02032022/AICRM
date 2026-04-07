<?php

declare(strict_types=1);

// BRD: CRM-LC-011 — Lead creation and retrieval via API
// BRD: CRM-LC-014 — Source field is required
// BRD: CRM-CR-001 — Consent is required
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// Seed permissions before every test in this file
beforeEach(function (): void {
    $this->seed(\Database\Seeders\PermissionSeeder::class);
});

// ─── Helpers ───────────────────────────────────────────────────────────────

function makeInstitutionAndCounsellor(): array
{
    $institution = Institution::create([
        'name' => 'Test University', 'code' => 'TU01', 'is_active' => true,
    ]);

    $counsellor = User::create([
        'name'           => 'Test Counsellor',
        'email'          => 'counsellor@tu.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $counsellor->givePermissionTo([
        'crm.leads.create',
        'crm.leads.view',
        'crm.leads.edit',
        'crm.leads.delete',
        'crm.leads.view_pii',
    ]);

    return [$institution, $counsellor];
}

function minimalLeadPayload(): array
{
    return [
        'first_name'           => 'Arjun',
        'last_name'            => 'Sharma',
        'mobile'               => '9876543210',
        'source'               => LeadSource::WALK_IN->value,
        'consent_given'        => true,
        'consent_form_version' => 'v1.0-test',
    ];
}

// ─── CRM-LC-011: Manual Lead Creation ──────────────────────────────────────

test('counsellor can create a lead via API (CRM-LC-011)', function (): void {
    [$institution, $counsellor] = makeInstitutionAndCounsellor();

    $response = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', minimalLeadPayload());

    $response->assertStatus(201)
        ->assertJsonPath('data.first_name', 'Arjun')
        ->assertJsonPath('data.last_name', 'Sharma')
        ->assertJsonPath('data.status', LeadStatus::NEW_ENQUIRY->value)
        ->assertJsonPath('data.temperature', LeadTemperature::COLD->value)
        ->assertJsonMissing(['id']);          // never expose auto-increment ID

    // Confirm lead saved to DB
    expect(Lead::withoutGlobalScopes()->where('institution_id', $institution->id)->count())->toBe(1);
});

test('created lead has uuid not sequential id in response (CRM-LC-011)', function (): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $response = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', minimalLeadPayload());

    $response->assertStatus(201);
    $uuid = $response->json('data.uuid');
    expect($uuid)->toBeString()->toMatch('/^[0-9a-f\-]{36}$/');
});

// ─── CRM-LC-014: Source is mandatory ───────────────────────────────────────

test('lead creation fails without source (CRM-LC-014)', function (): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $payload = minimalLeadPayload();
    unset($payload['source']);

    $response = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', $payload);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('errors.source'))->not->toBeNull();
});

test('lead creation fails with invalid source value (CRM-LC-014)', function (): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $payload          = minimalLeadPayload();
    $payload['source'] = 'invalid_source_xyz';

    $response = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', $payload);

    $response->assertStatus(422);
});

test('all valid lead sources are accepted (CRM-LC-014)', function (LeadSource $source): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $payload           = minimalLeadPayload();
    $payload['source'] = $source->value;

    $response = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.source', $source->value);
})->with(LeadSource::cases());

// ─── CRM-CR-001: Consent is mandatory ──────────────────────────────────────

test('lead creation fails when consent_given is false (CRM-CR-001)', function (): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $payload                  = minimalLeadPayload();
    $payload['consent_given'] = false;

    $response = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', $payload);

    $response->assertStatus(422);
    expect($response->json('errors.consent_given'))->not->toBeNull();
});

test('consent fields are stored at creation time (CRM-CR-001)', function (): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $response = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', minimalLeadPayload());

    $response->assertStatus(201);
    $uuid = $response->json('data.uuid');

    $lead = Lead::withoutGlobalScopes()->where('uuid', $uuid)->firstOrFail();

    expect($lead->consent_given)->toBeTrue();
    expect($lead->consent_timestamp)->not->toBeNull();
    expect($lead->consent_form_version)->toBe('v1.0-test');
});

// ─── Validation: Mobile ────────────────────────────────────────────────────

test('lead creation fails with invalid mobile number', function (string $mobile): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $payload           = minimalLeadPayload();
    $payload['mobile'] = $mobile;

    $response = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', $payload);

    $response->assertStatus(422);
})->with(['1234567890', '123', 'abcdefghij', '5876543210']);

test('lead creation accepts valid 10-digit mobile starting with 6-9', function (): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $payload           = minimalLeadPayload();
    $payload['mobile'] = '8876543210';

    $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', $payload)
        ->assertStatus(201);
});

// ─── RBAC ──────────────────────────────────────────────────────────────────

test('user without crm.leads.create permission gets 403', function (): void {
    $institution = Institution::create(['name' => 'X', 'code' => 'X01', 'is_active' => true]);
    $user        = User::create([
        'name' => 'No Perm', 'email' => 'noperm@x.com',
        'password' => bcrypt('p'), 'institution_id' => $institution->id,
    ]);
    // No permissions granted

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/leads', minimalLeadPayload());

    $response->assertStatus(403);
});

// ─── Multi-tenancy ─────────────────────────────────────────────────────────

test('lead is scoped to the creating user institution (NFR-MT-001)', function (): void {
    [$instA, $counsellorA] = makeInstitutionAndCounsellor();
    $instB = Institution::create(['name' => 'Inst B', 'code' => 'IB01', 'is_active' => true]);
    $counsellorB = User::create([
        'name' => 'B Counsellor', 'email' => 'b@b.com',
        'password' => bcrypt('p'), 'institution_id' => $instB->id,
    ]);
    $counsellorB->givePermissionTo(['crm.leads.view', 'crm.leads.create']);

    // Counsellor A creates a lead
    $res = $this->actingAs($counsellorA, 'sanctum')
        ->postJson('/api/v1/crm/leads', minimalLeadPayload());
    $res->assertStatus(201);
    $uuid = $res->json('data.uuid');

    // Counsellor B cannot see Inst A's lead
    $this->actingAs($counsellorB, 'sanctum')
        ->getJson("/api/v1/crm/leads/{$uuid}")
        ->assertStatus(404);
});

// ─── GET /leads/{uuid} ─────────────────────────────────────────────────────

test('counsellor can retrieve a lead by uuid', function (): void {
    [, $counsellor] = makeInstitutionAndCounsellor();

    $createRes = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', minimalLeadPayload());
    $uuid = $createRes->json('data.uuid');

    $this->actingAs($counsellor, 'sanctum')
        ->getJson("/api/v1/crm/leads/{$uuid}")
        ->assertStatus(200)
        ->assertJsonPath('data.uuid', $uuid)
        ->assertJsonPath('data.full_name', 'Arjun Sharma');
});

// ─── PII visibility ────────────────────────────────────────────────────────

test('mobile is hidden without view_pii permission', function (): void {
    $institution = Institution::create(['name' => 'Z', 'code' => 'Z01', 'is_active' => true]);
    $viewer      = User::create([
        'name' => 'Viewer', 'email' => 'v@z.com',
        'password' => bcrypt('p'), 'institution_id' => $institution->id,
    ]);
    $viewer->givePermissionTo(['crm.leads.create', 'crm.leads.view']); // no view_pii

    $createRes = $this->actingAs($viewer, 'sanctum')
        ->postJson('/api/v1/crm/leads', minimalLeadPayload());
    $uuid = $createRes->json('data.uuid');

    $res = $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/v1/crm/leads/{$uuid}");

    $res->assertStatus(200);
    // mobile should not be present in response
    expect($res->json('data.mobile'))->toBeNull();
});

// ─── Soft delete ───────────────────────────────────────────────────────────

test('deleting a lead soft-deletes — record remains in DB (CRM-LC-011)', function (): void {
    [, $counsellor] = makeInstitutionAndCounsellor();
    $counsellor->givePermissionTo('crm.leads.delete');

    $createRes = $this->actingAs($counsellor, 'sanctum')
        ->postJson('/api/v1/crm/leads', minimalLeadPayload());
    $uuid = $createRes->json('data.uuid');

    $this->actingAs($counsellor, 'sanctum')
        ->deleteJson("/api/v1/crm/leads/{$uuid}")
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    // Lead still exists in DB with deleted_at set
    expect(Lead::withTrashed()->whereUuid($uuid)->exists())->toBeTrue();
    // But normal query hides it (soft-delete scope active)
    expect(Lead::whereUuid($uuid)->withoutGlobalScope(\App\Models\CRM\Scopes\InstitutionScope::class)->exists())->toBeFalse();
});

// ─── LeadStatus enum ───────────────────────────────────────────────────────

test('LeadStatus transitions follow allowed pipeline', function (): void {
    $status = LeadStatus::NEW_ENQUIRY;

    expect($status->canTransitionTo(LeadStatus::CONTACTED))->toBeTrue();
    expect($status->canTransitionTo(LeadStatus::LOST))->toBeTrue();
    expect($status->canTransitionTo(LeadStatus::ENROLLED))->toBeFalse();
});

test('LeadTemperature derives from score correctly', function (): void {
    expect(LeadTemperature::fromScore(80))->toBe(LeadTemperature::HOT);
    expect(LeadTemperature::fromScore(50))->toBe(LeadTemperature::WARM);
    expect(LeadTemperature::fromScore(30))->toBe(LeadTemperature::COLD);
    expect(LeadTemperature::fromScore(0))->toBe(LeadTemperature::COLD);
});

test('LeadSource optionsForSelect returns all sources', function (): void {
    $options = LeadSource::optionsForSelect();
    expect(count($options))->toBe(count(LeadSource::cases()));
});
