<?php

declare(strict_types=1);

use App\Jobs\CRM\Communication\DiallerJob;
use App\Jobs\CRM\Communication\ProcessOutboundCallJob;
use App\Models\CRM\CallLog;
use App\Models\CRM\DiallerLog;
use App\Models\CRM\DiallerSession;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Communication\DiallerService;
use App\Services\CRM\Communication\VoiceService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeDiallerContext(): array
{
    $institution = Institution::create([
        'name' => 'Dialler Test Institute',
        'code' => 'DTI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Dialler Agent',
        'email' => 'dialler-agent@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.communication.send']);

    $lead = Lead::create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Ravi',
        'last_name' => 'Sharma',
        'mobile' => '9876500011',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'assigned_counsellor_id' => $user->id,
        'lead_score' => 67,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
    ]);

    return [$institution, $user, $lead];
}

it('starts dialler session from web and queues dialler job', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [, $user, $lead] = makeDiallerContext();

    Queue::fake();

    $this->actingAs($user)
        ->post(route('crm.communication.voice.dialler.start'), [
            'campaign_name' => 'Admission Campaign Alpha',
            'lead_limit' => 10,
            'lead_uuids' => [$lead->uuid],
        ])
        ->assertRedirect(route('crm.communication.voice.dialler.index'));

    $session = DiallerSession::first();

    expect($session)->not->toBeNull();
    expect($session?->campaign_name)->toBe('Admission Campaign Alpha');
    expect($session?->total_leads)->toBe(1);
    expect(DiallerLog::count())->toBe(1);

    Queue::assertPushedOn('crm-telecalling', DiallerJob::class);
});

it('processes one queued call and waits for completion before queuing next', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $user, $leadOne] = makeDiallerContext();

    $leadTwo = Lead::create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Anita',
        'last_name' => 'Joshi',
        'mobile' => '9876500022',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'assigned_counsellor_id' => $user->id,
        'lead_score' => 64,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
    ]);

    $session = DiallerSession::create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'started_by' => $user->id,
        'campaign_name' => 'Manual Progression Session',
        'status' => 'QUEUED',
        'total_leads' => 2,
        'queued_calls' => 2,
    ]);

    DiallerLog::create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'dialler_session_id' => $session->id,
        'lead_id' => $leadOne->id,
        'queue_order' => 1,
        'status' => 'QUEUED',
    ]);

    DiallerLog::create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'dialler_session_id' => $session->id,
        'lead_id' => $leadTwo->id,
        'queue_order' => 2,
        'status' => 'QUEUED',
    ]);

    Queue::fake();

    app(DiallerService::class)->processNextCall($session->uuid);

    $session->refresh();
    $firstLog = DiallerLog::query()->where('dialler_session_id', $session->id)->where('queue_order', 1)->firstOrFail();

    expect($session->status->value)->toBe('ACTIVE');
    expect($session->queued_calls)->toBe(1);
    expect($session->placed_calls)->toBe(1);
    expect($firstLog->status->value)->toBe('PLACED');
    expect($firstLog->call_log_id)->not->toBeNull();

    Queue::assertPushedOn('crm-comms-voice', ProcessOutboundCallJob::class);
    Queue::assertNotPushed(DiallerJob::class);

    $callLog = CallLog::findOrFail($firstLog->call_log_id);
    app(VoiceService::class)->finaliseCallLog($callLog, [
        'duration_seconds' => 180,
        'disposition' => 'INTERESTED',
        'notes' => 'Completed first call',
    ]);

    Queue::assertPushedOn('crm-telecalling', DiallerJob::class);
});

it('marks session completed when last queued dialler call is completed', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $user, $lead] = makeDiallerContext();

    $session = DiallerSession::create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'started_by' => $user->id,
        'campaign_name' => 'Single Lead Session',
        'status' => 'QUEUED',
        'total_leads' => 1,
        'queued_calls' => 1,
    ]);

    DiallerLog::create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'dialler_session_id' => $session->id,
        'lead_id' => $lead->id,
        'queue_order' => 1,
        'status' => 'QUEUED',
    ]);

    Queue::fake();

    app(DiallerService::class)->processNextCall($session->uuid);

    $log = DiallerLog::query()->where('dialler_session_id', $session->id)->firstOrFail();
    $callLog = CallLog::findOrFail($log->call_log_id);

    app(VoiceService::class)->finaliseCallLog($callLog, [
        'duration_seconds' => 120,
        'disposition' => 'CALL_BACK',
        'notes' => 'Follow up later',
    ]);

    $session->refresh();

    expect($session->status->value)->toBe('COMPLETED');
    expect($session->queued_calls)->toBe(0);
    expect($session->ended_at)->not->toBeNull();
});
