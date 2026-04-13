<?php

declare(strict_types=1);

use App\Models\CRM\CallScript;
use App\Models\CRM\CallScriptStep;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeCallScriptWebContext(): array
{
    $institution = Institution::create([
        'name' => 'Call Script Web Institute',
        'code' => 'CSWI',
        'is_active' => true,
    ]);

    $agent = User::create([
        'name' => 'Call Script Agent',
        'email' => 'call-script-web@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $agent->givePermissionTo(['crm.communication.send']);

    return [$institution, $agent];
}

it('renders call scripts screen for authorised user', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [, $agent] = makeCallScriptWebContext();

    $this->actingAs($agent)
        ->get(route('crm.communication.voice.scripts.index'))
        ->assertOk()
        ->assertSee('Call Scripts');
});

it('creates script from web form', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [, $agent] = makeCallScriptWebContext();

    $this->actingAs($agent)
        ->post(route('crm.communication.voice.scripts.store'), [
            'name' => 'Web Script',
            'status' => 'active',
            'steps' => [
                [
                    'step_key' => 'intro',
                    'prompt_text' => 'Are you available for counselling this week?',
                    'response_type' => 'text',
                    'default_next_step_key' => 'close',
                ],
                [
                    'step_key' => 'close',
                    'prompt_text' => 'Thank you for your time.',
                    'response_type' => 'text',
                    'is_terminal' => true,
                ],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('call_scripts', ['name' => 'Web Script']);
    $this->assertDatabaseHas('call_script_steps', ['step_key' => 'intro']);
});

it('resolves next step from runner', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $agent] = makeCallScriptWebContext();

    $script = CallScript::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Runner Script',
        'status' => 'active',
        'is_default' => false,
        'created_by' => $agent->id,
    ]);

    CallScriptStep::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'call_script_id' => $script->id,
        'step_key' => 'intro',
        'step_order' => 1,
        'prompt_text' => 'Do you need hostel support?',
        'response_type' => 'text',
        'branch_rules' => [
            ['operator' => 'contains', 'value' => 'yes', 'next_step_key' => 'hostel'],
        ],
        'default_next_step_key' => 'close',
        'is_terminal' => false,
    ]);

    CallScriptStep::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'call_script_id' => $script->id,
        'step_key' => 'hostel',
        'step_order' => 2,
        'prompt_text' => 'We can share hostel fee details.',
        'response_type' => 'text',
        'is_terminal' => false,
    ]);

    $this->actingAs($agent)
        ->post(route('crm.communication.voice.scripts.resolve', $script->uuid), [
            'current_step_key' => 'intro',
            'response' => 'yes, please share details',
        ])
        ->assertOk()
        ->assertSee('Next step resolved: hostel');
});

it('archives script from web screen', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $agent] = makeCallScriptWebContext();

    $script = CallScript::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Delete Script',
        'status' => 'active',
        'is_default' => false,
        'created_by' => $agent->id,
    ]);

    $this->actingAs($agent)
        ->delete(route('crm.communication.voice.scripts.destroy', $script->uuid))
        ->assertRedirect(route('crm.communication.voice.scripts.index'));

    $this->assertSoftDeleted('call_scripts', ['id' => $script->id]);
});
