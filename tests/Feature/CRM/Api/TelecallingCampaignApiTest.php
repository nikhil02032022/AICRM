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

function makeTelecallingCampaignApiContext(): array
{
    $institution = Institution::create([
        'name' => 'Telecalling API Institute',
        'code' => 'TCA',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Campaign Api User',
        'email' => 'campaign-api@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.campaigns.manage', 'crm.communication.send']);

    $agent = User::create([
        'name' => 'Campaign Api Agent',
        'email' => 'campaign-api-agent@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Neha',
        'last_name' => 'Iyer',
        'mobile' => '9876500092',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'hot',
        'lead_score' => 81,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
    ]);

    return [$institution, $user, $agent, $lead];
}

beforeEach(function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
});

it('creates and launches telecalling campaign through api', function (): void {
    /** @var \Tests\TestCase $this */
    [, $user, $agent, $lead] = makeTelecallingCampaignApiContext();

    Queue::fake();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/voice/campaigns', [
            'name' => 'API Telecalling Sprint',
            'description' => 'Focused outbound campaign',
            'status' => 'DRAFT',
            'agent_ids' => [$agent->id],
            'lead_uuids' => [$lead->uuid],
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'API Telecalling Sprint');

    $campaign = TelecallingCampaign::query()->where('name', 'API Telecalling Sprint')->firstOrFail();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/voice/campaigns/'.$campaign->uuid.'/launch')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.campaign.status', 'ACTIVE')
        ->assertJsonPath('data.progress.total_leads', 1);

    Queue::assertPushedOn('crm-telecalling', DiallerJob::class);
});

it('lists telecalling campaigns through api', function (): void {
    /** @var \Tests\TestCase $this */
    [$institution, $user] = makeTelecallingCampaignApiContext();

    TelecallingCampaign::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Seeded Campaign',
        'status' => 'DRAFT',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/voice/campaigns')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Telecalling campaigns fetched successfully.');
});
