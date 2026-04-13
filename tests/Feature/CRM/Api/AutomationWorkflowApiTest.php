<?php

declare(strict_types=1);

use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\WorkflowActionExecution;
use App\Models\CRM\WorkflowInstance;
use App\Models\CRM\WorkflowStep;
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

function makeAutomationAdmin(string $suffix = 'a'): array
{
    $institution = Institution::create([
        'name' => 'Automation Institute '.$suffix,
        'code' => 'MA'.strtoupper($suffix),
        'is_active' => true,
    ]);

    $admin = User::create([
        'name' => 'Automation Admin '.$suffix,
        'email' => 'automation-'.$suffix.'@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $admin->givePermissionTo('crm.campaigns.manage');

    return [$institution, $admin];
}

function workflowPayload(string $name = 'Lead Nurture Journey'): array
{
    return [
        'name' => $name,
        'description' => 'MA-001 foundation workflow',
        'status' => 'draft',
        'trigger_type' => 'lead_created',
        'trigger_config' => ['source' => 'website_form'],
        'steps' => [
            [
                'order' => 0,
                'node_type' => 'trigger',
                'name' => 'Lead Created',
                'config' => ['event' => 'lead_created'],
            ],
            [
                'order' => 1,
                'node_type' => 'action',
                'name' => 'Send Welcome Email',
                'config' => ['template' => 'welcome_1'],
                'delay_minutes' => 60,
            ],
        ],
    ];
}

it('can create automation workflow via API', function (): void {
    [, $admin] = makeAutomationAdmin();

    $response = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/automation/workflows', workflowPayload())
        ->assertCreated();

    $response->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Lead Nurture Journey')
        ->assertJsonPath('data.steps.0.node_type', 'trigger')
        ->assertJsonPath('data.steps.1.delay_minutes', 60);

    assertDatabaseHas('automation_workflows', [
        'name' => 'Lead Nurture Journey',
        'trigger_type' => 'lead_created',
    ]);
});

it('scopes automation workflows by institution', function (): void {
    [, $adminA] = makeAutomationAdmin('a');
    [, $adminB] = makeAutomationAdmin('b');

    actingAs($adminA, 'sanctum')
        ->postJson('/api/v1/crm/automation/workflows', workflowPayload('Institution A Workflow'))
        ->assertCreated();

    actingAs($adminB, 'sanctum')
        ->postJson('/api/v1/crm/automation/workflows', workflowPayload('Institution B Workflow'))
        ->assertCreated();

    actingAs($adminA, 'sanctum')
        ->getJson('/api/v1/crm/automation/workflows')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Institution A Workflow');
});

it('cannot view workflow from another institution', function (): void {
    [, $adminA] = makeAutomationAdmin('a');
    [, $adminB] = makeAutomationAdmin('b');

    $created = actingAs($adminA, 'sanctum')
        ->postJson('/api/v1/crm/automation/workflows', workflowPayload('Private Workflow'))
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/automation/workflows/'.$uuid)
        ->assertNotFound();
});

it('supports steps_json payload parsing', function (): void {
    [, $admin] = makeAutomationAdmin('c');

    $payload = workflowPayload('JSON Workflow');
    unset($payload['steps']);
    $payload['steps_json'] = json_encode([
        [
            'id' => 'step-json-1',
            'order' => 0,
            'node_type' => 'trigger',
            'name' => 'Form Submitted',
            'config' => ['event' => 'form_submitted'],
        ],
        [
            'id' => 'step-json-2',
            'order' => 1,
            'node_type' => 'action',
            'name' => 'Notify Counsellor',
            'config' => ['channel' => 'email'],
        ],
    ], JSON_THROW_ON_ERROR);

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/automation/workflows', $payload)
        ->assertCreated()
        ->assertJsonPath('data.steps.0.name', 'Form Submitted')
        ->assertJsonPath('data.steps.1.name', 'Notify Counsellor');
});

it('can update and delete automation workflow via API', function (): void {
    [, $admin] = makeAutomationAdmin('d');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/automation/workflows', workflowPayload('Lifecycle Workflow'))
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/automation/workflows/'.$uuid, [
            'status' => 'active',
            'name' => 'Lifecycle Workflow Updated',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.name', 'Lifecycle Workflow Updated');

    actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/crm/automation/workflows/'.$uuid)
        ->assertOk();

    $workflow = AutomationWorkflow::withoutGlobalScopes()->where('uuid', $uuid)->firstOrFail();
    expect($workflow->deleted_at)->not()->toBeNull();
});

it('returns automation workflow performance report via API', function (): void {
    [, $admin] = makeAutomationAdmin('perf');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/automation/workflows', workflowPayload('Performance Workflow'))
        ->assertCreated();

    $workflowUuid = (string) $created->json('data.uuid');

    $workflow = AutomationWorkflow::withoutGlobalScopes()
        ->where('uuid', $workflowUuid)
        ->firstOrFail();

    $step = WorkflowStep::withoutGlobalScopes()
        ->where('automation_workflow_id', $workflow->id)
        ->where('node_type', 'action')
        ->firstOrFail();

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $workflow->institution_id,
        'campus_id' => null,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => null,
        'status' => 'completed',
        'current_workflow_step_id' => $step->id,
        'started_at' => now()->subHour(),
        'completed_at' => now()->subMinutes(30),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    WorkflowActionExecution::withoutGlobalScopes()->create([
        'institution_id' => $workflow->institution_id,
        'campus_id' => null,
        'workflow_instance_id' => $instance->id,
        'workflow_step_id' => $step->id,
        'action_type' => 'send_email',
        'status' => 'success',
        'payload' => ['template' => 'welcome_1'],
        'result' => ['message' => 'ok'],
        'executed_at' => now()->subMinutes(40),
    ]);

    actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/automation/workflows-performance?days=30&workflow_uuid='.$workflowUuid)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.summary.instances_total', 1)
        ->assertJsonPath('data.summary.actions_success', 1)
        ->assertJsonPath('data.workflows.0.workflow_uuid', $workflowUuid)
        ->assertJsonPath('data.workflows.0.completion_rate', 100)
        ->assertJsonPath('data.workflows.0.action_success_rate', 100);
});
