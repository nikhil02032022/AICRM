<?php

declare(strict_types=1);

use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallStatus;
use App\Enums\CRM\TelephonyProvider;
use App\Jobs\CRM\Communication\ProcessTelephonyWebhookJob;
use App\Models\CRM\CallLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeCallRecordingContext(): array
{
    $institution = Institution::create([
        'name' => 'Recording Test Institute',
        'code' => 'RTI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Recording Manager',
        'email' => 'recording-manager@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $user->givePermissionTo('crm.communication.send');

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Riya',
        'last_name' => 'Kapoor',
        'mobile' => '9876507777',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 70,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
    ]);

    return [$institution, $user, $lead];
}

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

// BRD: CRM-TC-008 — Auto recording URL is stored only for consented calls.
it('stores recording URL from telephony webhook when consent is given', function (): void {
    [, $user, $lead] = makeCallRecordingContext();

    $callLog = CallLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'lead_id' => $lead->id,
        'telephony_provider' => TelephonyProvider::EXOTEL->value,
        'provider_call_id' => 'call-1001',
        'direction' => CallDirection::OUTBOUND->value,
        'from_number' => '08040000011',
        'to_number' => '9876507777',
        'call_consent_given' => true,
        'status' => CallStatus::RINGING->value,
        'initiated_by' => $user->id,
        'called_at' => now(),
    ]);

    $job = new ProcessTelephonyWebhookJob([
        'call_id' => 'call-1001',
        'status' => 'COMPLETED',
        'duration' => 95,
        'recording_file' => 'https://recordings.example.com/call-1001.mp3',
    ], 'exotel');

    $job->handle();

    $fresh = $callLog->fresh();

    expect($fresh->status)->toBe(CallStatus::COMPLETED)
        ->and($fresh->duration_seconds)->toBe(95)
        ->and($fresh->recording_url)->toBe('https://recordings.example.com/call-1001.mp3');
});

// BRD: CRM-TC-008 + DPDP — Recording URL is never stored when consent is false.
it('does not store recording URL from telephony webhook when consent is not given', function (): void {
    [, $user, $lead] = makeCallRecordingContext();

    $callLog = CallLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'lead_id' => $lead->id,
        'telephony_provider' => TelephonyProvider::EXOTEL->value,
        'provider_call_id' => 'call-1002',
        'direction' => CallDirection::OUTBOUND->value,
        'from_number' => '08040000012',
        'to_number' => '9876507777',
        'call_consent_given' => false,
        'status' => CallStatus::RINGING->value,
        'initiated_by' => $user->id,
        'called_at' => now(),
    ]);

    $job = new ProcessTelephonyWebhookJob([
        'call_id' => 'call-1002',
        'status' => 'COMPLETED',
        'duration' => 102,
        'recording_url' => 'https://recordings.example.com/call-1002.mp3',
    ], 'exotel');

    $job->handle();

    $fresh = $callLog->fresh();

    expect($fresh->status)->toBe(CallStatus::COMPLETED)
        ->and($fresh->recording_url)->toBeNull();
});

// BRD: CRM-TC-008 — Web playback endpoint redirects to recording URL for consented calls.
it('allows recording playback redirect for consented call logs', function (): void {
    [, $user, $lead] = makeCallRecordingContext();

    $callLog = CallLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'lead_id' => $lead->id,
        'telephony_provider' => TelephonyProvider::EXOTEL->value,
        'provider_call_id' => 'call-1003',
        'direction' => CallDirection::OUTBOUND->value,
        'from_number' => '08040000013',
        'to_number' => '9876507777',
        'call_consent_given' => true,
        'recording_url' => 'https://recordings.example.com/call-1003.mp3',
        'status' => CallStatus::COMPLETED->value,
        'initiated_by' => $user->id,
        'called_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('crm.communication.voice.calls.recording', $callLog->uuid));

    $response->assertRedirect('https://recordings.example.com/call-1003.mp3');
});
