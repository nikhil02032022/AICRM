<?php

declare(strict_types=1);

use App\Jobs\CRM\Communication\DiallerJob;
use App\Models\CRM\DiallerSession;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeDiallerApiContext(): array
{
    $institution = Institution::create([
        'name' => 'Dialler API Institute',
        'code' => 'DAI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Dialler Api User',
        'email' => 'dialler-api@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.communication.send']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Nisha',
        'last_name' => 'Patel',
        'mobile' => '9876500012',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'hot',
        'assigned_counsellor_id' => $user->id,
        'lead_score' => 80,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
    ]);

    return [$institution, $user, $lead];
}

it('starts dialler session through api', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [, $user, $lead] = makeDiallerApiContext();

    Queue::fake();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/dialler/sessions', [
            'campaign_name' => 'API Power Dial',
            'lead_uuids' => [$lead->uuid],
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Dialler session started successfully.')
        ->assertJsonPath('data.total_leads', 1);

    expect(DiallerSession::count())->toBe(1);
    Queue::assertPushedOn('crm-telecalling', DiallerJob::class);
});

it('lists dialler sessions through api with standard envelope', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $user] = makeDiallerApiContext();

    DiallerSession::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'started_by' => $user->id,
        'campaign_name' => 'Seed Session',
        'status' => 'QUEUED',
        'total_leads' => 1,
        'queued_calls' => 1,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/dialler/sessions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Dialler sessions fetched successfully.');
});

it('stops a dialler session through api', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $user] = makeDiallerApiContext();

    $session = DiallerSession::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'started_by' => $user->id,
        'campaign_name' => 'Stoppable Session',
        'status' => 'ACTIVE',
        'total_leads' => 2,
        'queued_calls' => 1,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/dialler/sessions/'.$session->uuid.'/stop')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'STOPPED');
});

it('queues next dialler step through api', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $user] = makeDiallerApiContext();

    Queue::fake();

    $session = DiallerSession::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'started_by' => $user->id,
        'campaign_name' => 'Queue Next Session',
        'status' => 'ACTIVE',
        'total_leads' => 3,
        'queued_calls' => 2,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/dialler/sessions/'.$session->uuid.'/dispatch-next')
        ->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Dialler next call queued successfully.');

    Queue::assertPushedOn('crm-telecalling', DiallerJob::class);
});
