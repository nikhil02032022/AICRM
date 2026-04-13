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

function makeCallMonitorWebContext(): array
{
    $institution = Institution::create([
        'name' => 'Call Monitor Institute',
        'code' => 'CMI',
        'is_active' => true,
    ]);

    $supervisor = User::create([
        'name' => 'Call Supervisor',
        'email' => 'call-supervisor@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $supervisor->givePermissionTo(['crm.communication.send']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Arjun',
        'last_name' => 'Nair',
        'mobile' => '9876500033',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
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
        'from_number' => '08040000001',
        'to_number' => '9876500033',
        'status' => CallStatus::IN_PROGRESS,
        'call_consent_given' => true,
        'initiated_by' => $supervisor->id,
        'called_at' => now(),
    ]);

    return [$institution, $supervisor, $callLog];
}

it('renders supervisor monitor page', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [, $supervisor] = makeCallMonitorWebContext();

    $this->actingAs($supervisor)
        ->get(route('crm.communication.voice.monitor.index'))
        ->assertOk()
        ->assertSee('Supervisor Call Monitoring');
});

it('starts monitoring session from web', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [, $supervisor, $callLog] = makeCallMonitorWebContext();

    $this->actingAs($supervisor)
        ->post(route('crm.communication.voice.monitor.store'), [
            'call_log_uuid' => $callLog->uuid,
            'mode' => 'LISTEN',
            'notes' => 'QA check',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('call_monitor_logs', [
        'call_log_id' => $callLog->id,
        'supervisor_id' => $supervisor->id,
        'mode' => 'LISTEN',
        'status' => 'ACTIVE',
    ]);
});

it('stops monitoring session from web', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $supervisor, $callLog] = makeCallMonitorWebContext();

    $monitorLog = CallMonitorLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'call_log_id' => $callLog->id,
        'supervisor_id' => $supervisor->id,
        'mode' => 'WHISPER',
        'status' => 'ACTIVE',
        'consent_validated' => true,
        'started_at' => now()->subMinutes(2),
    ]);

    $this->actingAs($supervisor)
        ->post(route('crm.communication.voice.monitor.stop', $monitorLog->uuid), [
            'notes' => 'Session complete',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('call_monitor_logs', [
        'id' => $monitorLog->id,
        'status' => 'ENDED',
    ]);
});
