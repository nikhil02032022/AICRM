<?php

declare(strict_types=1);

use App\Models\CRM\CallScript;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
});

function makeCallScriptApiContext(): array
{
    $institution = Institution::create([
        'name' => 'Call Script API Institute',
        'code' => 'CSAI',
        'is_active' => true,
    ]);

    $manager = User::create([
        'name' => 'Script Manager',
        'email' => 'call-script-api@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $manager->givePermissionTo(['crm.communication.send']);

    return [$institution, $manager];
}

function callScriptPayload(): array
{
    return [
        'name' => 'Admission Discovery Script',
        'status' => 'active',
        'description' => 'Discovery flow for intake counselling calls',
        'steps' => [
            [
                'step_key' => 'intro',
                'step_order' => 1,
                'prompt_text' => 'Are you planning to apply this year?',
                'response_type' => 'text',
                'branch_rules' => [
                    ['operator' => 'contains', 'value' => 'yes', 'next_step_key' => 'budget'],
                ],
                'default_next_step_key' => 'close',
            ],
            [
                'step_key' => 'budget',
                'step_order' => 2,
                'prompt_text' => 'What is your approximate budget?',
                'response_type' => 'number',
                'default_next_step_key' => 'close',
            ],
            [
                'step_key' => 'close',
                'step_order' => 3,
                'prompt_text' => 'Thank you, we will share next steps shortly.',
                'response_type' => 'text',
                'is_terminal' => true,
            ],
        ],
    ];
}

it('creates call script via api', function (): void {
    /** @var \Tests\TestCase $this */
    [, $manager] = makeCallScriptApiContext();

    $response = $this->actingAs($manager, 'sanctum')
        ->postJson('/api/v1/crm/voice/call-scripts', callScriptPayload());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Admission Discovery Script')
        ->assertJsonPath('data.steps.0.step_key', 'intro');

    $this->assertDatabaseHas('call_scripts', ['name' => 'Admission Discovery Script']);
});

it('lists institution call scripts only', function (): void {
    /** @var \Tests\TestCase $this */
    [$institutionA, $managerA] = makeCallScriptApiContext();

    $institutionB = Institution::create([
        'name' => 'Other Institute',
        'code' => 'OTHI',
        'is_active' => true,
    ]);

    CallScript::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institutionA->id,
        'name' => 'Inst A Script',
        'status' => 'active',
        'is_default' => false,
    ]);

    CallScript::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institutionB->id,
        'name' => 'Inst B Script',
        'status' => 'draft',
        'is_default' => false,
    ]);

    $response = $this->actingAs($managerA, 'sanctum')
        ->getJson('/api/v1/crm/voice/call-scripts');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Inst A Script');
});

it('updates and resolves next step via api', function (): void {
    /** @var \Tests\TestCase $this */
    [$institution, $manager] = makeCallScriptApiContext();

    $script = CallScript::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Old Script',
        'status' => 'draft',
        'is_default' => false,
        'created_by' => $manager->id,
    ]);

    $payload = callScriptPayload();
    $payload['name'] = 'Updated Discovery Script';

    $this->actingAs($manager, 'sanctum')
        ->putJson('/api/v1/crm/voice/call-scripts/'.$script->uuid, $payload)
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Discovery Script');

    $resolve = $this->actingAs($manager, 'sanctum')
        ->postJson('/api/v1/crm/voice/call-scripts/'.$script->uuid.'/resolve', [
            'current_step_key' => 'intro',
            'response' => 'yes, this year',
        ]);

    $resolve->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.next_step.step_key', 'budget')
        ->assertJsonPath('data.is_terminal', false);
});

it('archives call script via api', function (): void {
    /** @var \Tests\TestCase $this */
    [$institution, $manager] = makeCallScriptApiContext();

    $script = CallScript::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Archive Script',
        'status' => 'active',
        'is_default' => false,
        'created_by' => $manager->id,
    ]);

    $this->actingAs($manager, 'sanctum')
        ->deleteJson('/api/v1/crm/voice/call-scripts/'.$script->uuid)
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('call_scripts', ['id' => $script->id]);
});
