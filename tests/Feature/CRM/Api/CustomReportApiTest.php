<?php

declare(strict_types=1);

// BRD: CRM-AR-001 — Custom analytics report API tests
// Covers: CRUD, run, export, RBAC, multi-tenancy isolation

use App\Models\CRM\CustomReport;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(PermissionSeeder::class);
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeReportAdmin(string $suffix = 'a'): array
{
    $institution = Institution::create([
        'name'      => 'Report Inst ' . $suffix,
        'code'      => 'RPT' . strtoupper($suffix),
        'is_active' => true,
    ]);

    $admin = User::create([
        'name'           => 'Report Admin ' . $suffix,
        'email'          => 'rpt-admin-' . $suffix . '@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $admin->givePermissionTo([
        'crm.reports.view',
        'crm.reports.manage',
        'crm.reports.export',
    ]);

    return [$institution, $admin];
}

function reportPayload(array $overrides = []): array
{
    return array_merge([
        'name'            => 'Lead Source Analysis',
        'entity'          => 'leads',
        'selected_fields' => ['id', 'first_name', 'source'],
        'filters'         => [],
        'sort_field'      => 'created_at',
        'sort_direction'  => 'desc',
    ], $overrides);
}

// ─── CREATE ─────────────────────────────────────────────────────────────────

it('admin can create a custom report (CRM-AR-001)', function (): void {
    [$institution, $admin] = makeReportAdmin();

    $response = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload())
        ->assertCreated();

    $response->assertJsonPath('data.name', 'Lead Source Analysis')
        ->assertJsonPath('data.entity', 'leads');

    assertDatabaseHas('custom_reports', [
        'name'           => 'Lead Source Analysis',
        'entity'         => 'leads',
        'institution_id' => $institution->id,
    ]);
});

it('validates required fields on report creation (CRM-AR-001)', function (): void {
    [, $admin] = makeReportAdmin('b');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'entity', 'selected_fields']);
});

// ─── READ ───────────────────────────────────────────────────────────────────

it('lists reports scoped to the authenticated institution (CRM-AR-001)', function (): void {
    [$instA, $adminA] = makeReportAdmin('c');
    [$instB, $adminB] = makeReportAdmin('d');

    actingAs($adminA, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload(['name' => 'Report A']))
        ->assertCreated();

    actingAs($adminB, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload(['name' => 'Report B']))
        ->assertCreated();

    actingAs($adminA, 'sanctum')
        ->getJson('/api/v1/crm/reports/custom')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Report A');
});

it('cannot fetch a report belonging to another institution (CRM-AR-001)', function (): void {
    [$instA, $adminA] = makeReportAdmin('e');
    [$instB, $adminB] = makeReportAdmin('f');

    $report = CustomReport::withoutGlobalScopes()->create([
        'institution_id'  => $instA->id,
        'name'            => 'Private Report',
        'entity'          => 'leads',
        'selected_fields' => ['id', 'first_name'],
        'filters'         => [],
        'sort_field'      => 'created_at',
        'sort_direction'  => 'desc',
        'created_by'      => $adminA->id,
    ]);

    actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/reports/custom/' . $report->uuid)
        ->assertNotFound();
});

// ─── UPDATE ─────────────────────────────────────────────────────────────────

it('can update report name and selected_fields (CRM-AR-001)', function (): void {
    [, $admin] = makeReportAdmin('g');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/reports/custom/' . $uuid, [
            'name'            => 'Updated Report',
            'entity'          => 'leads',
            'selected_fields' => ['id', 'first_name', 'email', 'source'],
            'filters'         => [],
            'sort_field'      => 'created_at',
            'sort_direction'  => 'asc',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Report')
        ->assertJsonCount(4, 'data.selected_fields');
});

// ─── RUN ────────────────────────────────────────────────────────────────────

it('can run a report and receive results array (CRM-AR-001)', function (): void {
    [, $admin] = makeReportAdmin('h');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom/' . $uuid . '/run')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data' => ['headers', 'rows', 'total']]);
});

it('run updates last_run_at on the report (CRM-AR-001)', function (): void {
    [, $admin] = makeReportAdmin('i');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    expect($created->json('data.last_run_at'))->toBeNull();

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom/' . $uuid . '/run')
        ->assertOk();

    // last_run_at was touched
    assertDatabaseHas('custom_reports', [
        'uuid' => $uuid,
    ]);
    expect(
        \App\Models\CRM\CustomReport::withoutGlobalScopes()->where('uuid', $uuid)->value('last_run_at')
    )->not->toBeNull();
});

it('cannot run report without crm.reports.view permission (CRM-AR-001)', function (): void {
    [$institution, $admin] = makeReportAdmin('j');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    $noPerms = User::create([
        'name'           => 'No Perms',
        'email'          => 'no-perms-rpt@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    actingAs($noPerms, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom/' . $uuid . '/run')
        ->assertForbidden();
});

// ─── DELETE ─────────────────────────────────────────────────────────────────

it('can delete a custom report (CRM-AR-001)', function (): void {
    [, $admin] = makeReportAdmin('k');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/crm/reports/custom/' . $uuid)
        ->assertOk()
        ->assertJsonPath('success', true);
});

// ─── RBAC ───────────────────────────────────────────────────────────────────

it('user without manage permission cannot create report (CRM-AR-001)', function (): void {
    [$institution] = makeReportAdmin('l');

    $viewer = User::create([
        'name'           => 'Viewer Only',
        'email'          => 'viewer-rpt@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $viewer->givePermissionTo('crm.reports.view');

    actingAs($viewer, 'sanctum')
        ->postJson('/api/v1/crm/reports/custom', reportPayload())
        ->assertForbidden();
});

it('unauthenticated request is rejected (CRM-AR-001)', function (): void {
    $this->postJson('/api/v1/crm/reports/custom', reportPayload())
        ->assertUnauthorized();
});
