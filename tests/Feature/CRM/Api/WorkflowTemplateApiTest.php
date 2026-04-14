<?php

declare(strict_types=1);

// BRD: CRM-MA-003 — Workflow template API tests
// Covers: CRUD, global vs institution-scoped templates, import creates draft workflow,
//         used_count increment, cross-tenant isolation, RBAC

use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\Institution;
use App\Models\CRM\WorkflowTemplate;
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

function makeWorkflowAdmin(string $suffix = 'a'): array
{
    $institution = Institution::create([
        'name'      => 'WF Inst ' . $suffix,
        'code'      => 'WF' . strtoupper($suffix),
        'is_active' => true,
    ]);

    $admin = User::create([
        'name'           => 'WF Admin ' . $suffix,
        'email'          => 'wf-admin-' . $suffix . '@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $admin->givePermissionTo([
        'crm.settings.workflow-templates.view',
        'crm.settings.workflow-templates.manage',
    ]);

    return [$institution, $admin];
}

function templatePayload(array $overrides = []): array
{
    return array_merge([
        'name'         => 'Welcome Sequence',
        'category'     => 'lead_nurture',
        'trigger_type' => 'lead_created',
        'template_data' => [
            'steps' => [
                ['type' => 'email', 'delay' => 0, 'subject' => 'Welcome!'],
                ['type' => 'sms', 'delay' => 1440, 'body' => 'Hi, following up!'],
            ],
        ],
    ], $overrides);
}

// ─── CREATE ─────────────────────────────────────────────────────────────────

it('admin can create an institution-scoped workflow template (CRM-MA-003)', function (): void {
    [$institution, $admin] = makeWorkflowAdmin();

    $response = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates', templatePayload())
        ->assertCreated();

    $response->assertJsonPath('data.name', 'Welcome Sequence')
        ->assertJsonPath('data.is_global', false);

    assertDatabaseHas('workflow_templates', [
        'name'           => 'Welcome Sequence',
        'institution_id' => $institution->id,
        'is_global'      => false,
    ]);
});

it('validates required fields on template creation (CRM-MA-003)', function (): void {
    [, $admin] = makeWorkflowAdmin('b');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'category', 'trigger_type', 'template_data']);
});

// ─── GLOBAL TEMPLATES ────────────────────────────────────────────────────────

it('global template (institution_id=null) is visible to all institutions (CRM-MA-003)', function (): void {
    // Create global template directly (only superadmin can create global ones)
    $globalTemplate = WorkflowTemplate::create([
        'institution_id' => null,
        'name'           => 'Global Onboarding',
        'category'       => 'onboarding',
        'trigger_type'   => 'lead_created',
        'template_data'  => ['steps' => []],
        'is_global'      => true,
        'used_count'     => 0,
    ]);

    [$instA, $adminA] = makeWorkflowAdmin('c');
    [$instB, $adminB] = makeWorkflowAdmin('d');

    // Both institutions see the global template
    actingAs($adminA, 'sanctum')
        ->getJson('/api/v1/crm/workflow-templates')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Global Onboarding']);

    actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/workflow-templates')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Global Onboarding']);
});

// ─── ISOLATION ───────────────────────────────────────────────────────────────

it('institution-specific templates are NOT visible to other institutions (CRM-MA-003)', function (): void {
    [$instA, $adminA] = makeWorkflowAdmin('e');
    [$instB, $adminB] = makeWorkflowAdmin('f');

    actingAs($adminA, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates', templatePayload(['name' => 'Inst A Template']))
        ->assertCreated();

    actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/workflow-templates')
        ->assertOk()
        ->assertJsonMissing(['name' => 'Inst A Template']);
});

it('cannot fetch another institutions private template by UUID (CRM-MA-003)', function (): void {
    [$instA, $adminA] = makeWorkflowAdmin('g');
    [$instB, $adminB] = makeWorkflowAdmin('h');

    $template = WorkflowTemplate::create([
        'institution_id' => $instA->id,
        'name'           => 'Private Template',
        'category'       => 'lead_nurture',
        'trigger_type'   => 'lead_created',
        'template_data'  => ['steps' => []],
        'is_global'      => false,
        'used_count'     => 0,
    ]);

    actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/workflow-templates/' . $template->uuid)
        ->assertNotFound();
});

// ─── IMPORT ──────────────────────────────────────────────────────────────────

it('importing a template creates a draft AutomationWorkflow (CRM-MA-003)', function (): void {
    [$institution, $admin] = makeWorkflowAdmin('i');

    $template = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates', templatePayload())
        ->assertCreated();

    $uuid = $template->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates/' . $uuid . '/import')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data' => ['workflow_uuid']]);

    assertDatabaseHas('automation_workflows', [
        'institution_id' => $institution->id,
        'status'         => 'draft',
    ]);
});

it('importing a template increments used_count (CRM-MA-003)', function (): void {
    [$institution, $admin] = makeWorkflowAdmin('j');

    $template = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates', templatePayload())
        ->assertCreated();

    $uuid = $template->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates/' . $uuid . '/import')
        ->assertOk();

    actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/workflow-templates/' . $uuid)
        ->assertOk()
        ->assertJsonPath('data.used_count', 1);
});

// ─── UPDATE ──────────────────────────────────────────────────────────────────

it('can update template name and template_data (CRM-MA-003)', function (): void {
    [, $admin] = makeWorkflowAdmin('k');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates', templatePayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/workflow-templates/' . $uuid, [
            'name'          => 'Updated Template',
            'category'      => 'lead_nurture',
            'trigger_type'  => 'lead_created',
            'template_data' => ['steps' => []],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Template');
});

// ─── DELETE ──────────────────────────────────────────────────────────────────

it('can delete own workflow template (CRM-MA-003)', function (): void {
    [, $admin] = makeWorkflowAdmin('l');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates', templatePayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/crm/workflow-templates/' . $uuid)
        ->assertOk()
        ->assertJsonPath('success', true);
});

// ─── RBAC ────────────────────────────────────────────────────────────────────

it('user without manage permission cannot create workflow template (CRM-MA-003)', function (): void {
    [$institution] = makeWorkflowAdmin('m');

    $viewer = User::create([
        'name'           => 'Read Only',
        'email'          => 'wf-viewer@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $viewer->givePermissionTo('crm.settings.workflow-templates.view');

    actingAs($viewer, 'sanctum')
        ->postJson('/api/v1/crm/workflow-templates', templatePayload())
        ->assertForbidden();
});

it('unauthenticated request is rejected (CRM-MA-003)', function (): void {
    $this->postJson('/api/v1/crm/workflow-templates', templatePayload())
        ->assertUnauthorized();
});
