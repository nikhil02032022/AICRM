<?php

declare(strict_types=1);

use App\Jobs\CRM\Communication\DiallerJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\TelecallingCampaign;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeTelecallingCampaignWebContext(): array
{
    $institution = Institution::create([
        'name' => 'Telecalling Web Institute',
        'code' => 'TCW',
        'is_active' => true,
    ]);

    $manager = User::create([
        'name' => 'Campaign Manager',
        'email' => 'campaign-manager@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $manager->givePermissionTo(['crm.campaigns.manage', 'crm.communication.send']);

    $agent = User::create([
        'name' => 'Campaign Agent',
        'email' => 'campaign-agent@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Rahul',
        'last_name' => 'Mehra',
        'mobile' => '9876500091',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 76,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
    ]);

    return [$institution, $manager, $agent, $lead];
}

beforeEach(function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
});

it('creates and launches telecalling campaign from web', function (): void {
    /** @var \Tests\TestCase $this */
    [, $manager, $agent, $lead] = makeTelecallingCampaignWebContext();

    Queue::fake();

    $this->actingAs($manager)
        ->post(route('crm.communication.voice.campaigns.store'), [
            'name' => 'June Calling Sprint',
            'description' => 'Weekend conversion push',
            'status' => 'DRAFT',
            'agent_ids' => [$agent->id],
            'lead_uuids' => [$lead->uuid],
        ])
        ->assertRedirect();

    $campaign = TelecallingCampaign::query()->where('name', 'June Calling Sprint')->firstOrFail();

    $this->actingAs($manager)
        ->post(route('crm.communication.voice.campaigns.launch', $campaign->uuid))
        ->assertRedirect();

    $campaign->refresh();

    expect($campaign->status?->value)->toBe('ACTIVE');
    expect($campaign->launched_at)->not->toBeNull();

    $this->assertDatabaseHas('dialler_sessions', [
        'telecalling_campaign_id' => $campaign->id,
    ]);

    Queue::assertPushedOn('crm-telecalling', DiallerJob::class);
});

it('renders dedicated edit page and updates telecalling campaign from web', function (): void {
    /** @var \Tests\TestCase $this */
    [, $manager, $agent, $lead] = makeTelecallingCampaignWebContext();

    $this->actingAs($manager)
        ->post(route('crm.communication.voice.campaigns.store'), [
            'name' => 'Editable Campaign',
            'description' => 'Initial description',
            'status' => 'DRAFT',
            'agent_ids' => [$agent->id],
            'lead_uuids' => [$lead->uuid],
        ])
        ->assertRedirect();

    $campaign = TelecallingCampaign::query()->where('name', 'Editable Campaign')->firstOrFail();

    $this->actingAs($manager)
        ->get(route('crm.communication.voice.campaigns.edit', $campaign->uuid))
        ->assertOk()
        ->assertSee('Edit Telecalling Campaign')
        ->assertSee('Editable Campaign');

    $this->actingAs($manager)
        ->put(route('crm.communication.voice.campaigns.update', $campaign->uuid), [
            'name' => 'Editable Campaign Updated',
            'description' => 'Updated description',
            'status' => 'SCHEDULED',
            'agent_ids' => [$agent->id],
            'lead_uuids' => [$lead->uuid],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('telecalling_campaigns', [
        'id' => $campaign->id,
        'name' => 'Editable Campaign Updated',
        'status' => 'SCHEDULED',
    ]);
});
