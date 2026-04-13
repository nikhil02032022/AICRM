<?php

declare(strict_types=1);

use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallStatus;
use App\Enums\CRM\TelephonyProvider;
use App\Models\CRM\CallLog;
use App\Models\CRM\CallMonitorLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
});

function makeCallMonitorApiContext(): array
{
    $institution = Institution::create([
        'name' => 'Call Monitor API Institute',
        'code' => 'CMAI',
        'is_active' => true,
    ]);

    $supervisor = User::create([
        'name' => 'API Supervisor',
        'email' => 'call-monitor-api@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $supervisor->givePermissionTo(['crm.communication.send']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Meera',
        'last_name' => 'Iyer',
        'mobile' => '9876500044',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'hot',
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    $callLog = CallLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'telephony_provider' => TelephonyProvider::EXOTEL,
        'direction' => CallDirection::OUTBOUND,
        'from_number' => '08040000002',
        'to_number' => '9876500044',
        'status' => CallStatus::IN_PROGRESS,
        'call_consent_given' => true,
        'initiated_by' => $supervisor->id,
        'called_at' => now(),
    ]);

    return [$institution, $supervisor, $callLog];
}

it('starts monitoring session via api', function (): void {
    /** @var \Tests\TestCase $this */
    [, $supervisor, $callLog] = makeCallMonitorApiContext();

    $this->actingAs($supervisor, 'sanctum')
        ->postJson('/api/v1/crm/voice/call-monitor/sessions', [
            'call_log_uuid' => $callLog->uuid,
            'mode' => 'BARGE_IN',
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.mode', 'BARGE_IN');
});

it('lists monitor sessions and active calls via api', function (): void {
    /** @var \Tests\TestCase $this */
    [$institution, $supervisor, $callLog] = makeCallMonitorApiContext();

    CallMonitorLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'call_log_id' => $callLog->id,
        'supervisor_id' => $supervisor->id,
        'mode' => 'LISTEN',
        'status' => 'ACTIVE',
        'consent_validated' => true,
        'started_at' => now(),
    ]);

    $this->actingAs($supervisor, 'sanctum')
        ->getJson('/api/v1/crm/voice/call-monitor/sessions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Call monitoring data fetched successfully.')
        ->assertJsonPath('data.sessions.0.mode', 'LISTEN');
});

it('stops monitoring session via api', function (): void {
    /** @var \Tests\TestCase $this */
    [$institution, $supervisor, $callLog] = makeCallMonitorApiContext();

    $monitorLog = CallMonitorLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'call_log_id' => $callLog->id,
        'supervisor_id' => $supervisor->id,
        'mode' => 'WHISPER',
        'status' => 'ACTIVE',
        'consent_validated' => true,
        'started_at' => now()->subMinute(),
    ]);

    $this->actingAs($supervisor, 'sanctum')
        ->postJson('/api/v1/crm/voice/call-monitor/sessions/'.$monitorLog->uuid.'/stop', [
            'notes' => 'Stopped by QA lead',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'ENDED');
});

it('blocks monitor start when consent is missing', function (): void {
    /** @var \Tests\TestCase $this */
    [, $supervisor, $callLog] = makeCallMonitorApiContext();

    $callLog->update(['call_consent_given' => false]);

    $this->actingAs($supervisor, 'sanctum')
        ->postJson('/api/v1/crm/voice/call-monitor/sessions', [
            'call_log_uuid' => $callLog->uuid,
            'mode' => 'LISTEN',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'MONITOR_START_BLOCKED');
});
