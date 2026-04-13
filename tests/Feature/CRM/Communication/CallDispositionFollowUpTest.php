<?php

declare(strict_types=1);

use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallStatus;
use App\Enums\CRM\TelephonyProvider;
use App\Models\CRM\CallDispositionConfig;
use App\Models\CRM\CallLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeDispositionWebContext(): array
{
    $institution = Institution::create([
        'name' => 'Disposition Test Institute',
        'code' => 'DTI2',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Disposition Manager',
        'email' => 'disposition-manager@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.communication.send', 'crm.settings.manage', 'crm.sessions.create']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Kiran',
        'last_name' => 'Patel',
        'mobile' => '9876500055',
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
        'from_number' => '08040000003',
        'to_number' => '9876500055',
        'status' => CallStatus::IN_PROGRESS,
        'call_consent_given' => true,
        'initiated_by' => $user->id,
        'called_at' => now(),
    ]);

    return [$institution, $user, $lead, $callLog];
}

it('loads disposition settings and updates a configuration', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $user] = makeDispositionWebContext();

    $this->actingAs($user)
        ->get(route('crm.communication.voice.dispositions.index'))
        ->assertOk()
        ->assertSee('Call Disposition Settings');

    $config = CallDispositionConfig::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('code', 'CALL_BACK')
        ->firstOrFail();

    $this->actingAs($user)
        ->put(route('crm.communication.voice.dispositions.update', $config->uuid), [
            'label' => 'Call Back Required',
            'is_active' => 1,
            'requires_follow_up' => 1,
            'sort_order' => 3,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('call_disposition_configs', [
        'id' => $config->id,
        'label' => 'Call Back Required',
        'requires_follow_up' => true,
    ]);
});

it('redirects to follow-up scheduling prompt after callback disposition', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
    [$institution, $user, $lead, $callLog] = makeDispositionWebContext();

    $this->actingAs($user)
        ->get(route('crm.communication.voice.dispositions.index'))
        ->assertOk();

    $config = CallDispositionConfig::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('code', 'CALL_BACK')
        ->firstOrFail();

    CallDispositionConfig::withoutGlobalScopes()->where('id', $config->id)->update([
        'requires_follow_up' => true,
        'is_active' => true,
    ]);

    Queue::fake();

    $this->actingAs($user)
        ->post(route('crm.communication.voice.calls.disposition', $callLog->uuid), [
            'disposition' => 'CALL_BACK',
            'disposition_notes' => 'Requested follow-up tomorrow',
            'duration_seconds' => 120,
        ])
        ->assertRedirect(route('crm.leads.sessions.create', $lead->uuid));

    $this->assertEquals('CALL_BACK', $callLog->fresh()->disposition?->value);
    expect(session('follow_up_prompt'))->not->toBeNull();
});
