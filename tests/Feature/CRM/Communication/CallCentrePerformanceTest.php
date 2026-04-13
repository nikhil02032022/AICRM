<?php

declare(strict_types=1);

use App\Enums\CRM\CallStatus;
use App\Enums\CRM\LeadStatus;
use App\Models\CRM\CallLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeCallCentrePerformanceContext(): array
{
    $institution = Institution::create([
        'name' => 'Performance Dashboard Institute',
        'code' => 'PD',
        'is_active' => true,
    ]);

    $manager = User::create([
        'name' => 'Call Centre Manager',
        'email' => 'cc-manager@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $manager->givePermissionTo('crm.voice.performance');

    $agent1 = User::create([
        'name' => 'Agent One',
        'email' => 'agent1@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $agent2 = User::create([
        'name' => 'Agent Two',
        'email' => 'agent2@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $lead1 = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Priya',
        'last_name' => 'Kumar',
        'mobile' => '9876501234',
        'source' => 'referral',
        'status' => LeadStatus::ENROLLED->value,
        'temperature' => 'hot',
        'lead_score' => 85,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
        'assigned_counsellor_id' => $agent1->id,
        'status_changed_at' => now(),
    ]);

    $lead2 = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Amit',
        'last_name' => 'Sharma',
        'mobile' => '9876505678',
        'source' => 'walk_in',
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'temperature' => 'warm',
        'lead_score' => 72,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
        'assigned_counsellor_id' => $agent2->id,
    ]);

    // Agent1: 5 outbound calls, 3 connects, 1 conversion
    for ($i = 0; $i < 3; $i++) {
        CallLog::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'lead_id' => $lead1->id,
            'direction' => 'OUTBOUND',
            'telephony_provider' => 'EXOTEL',
            'from_number' => '08040000001',
            'to_number' => $lead1->mobile,
            'status' => CallStatus::COMPLETED->value,
            'disposition' => 'INTERESTED',
            'initiated_by' => $agent1->id,
            'duration_seconds' => 120 + ($i * 30),
            'called_at' => now(),
            'ended_at' => now()->addSeconds(120 + ($i * 30)),
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        CallLog::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'lead_id' => $lead1->id,
            'direction' => 'OUTBOUND',
            'telephony_provider' => 'EXOTEL',
            'from_number' => '08040000001',
            'to_number' => $lead1->mobile,
            'status' => CallStatus::FAILED->value,
            'disposition' => 'NOT_REACHABLE',
            'initiated_by' => $agent1->id,
            'duration_seconds' => 0,
            'called_at' => now(),
            'ended_at' => now(),
        ]);
    }

    // Agent2: 3 outbound calls, 2 connects, 0 conversions
    for ($i = 0; $i < 2; $i++) {
        CallLog::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'lead_id' => $lead2->id,
            'direction' => 'OUTBOUND',
            'telephony_provider' => 'EXOTEL',
            'from_number' => '08040000002',
            'to_number' => $lead2->mobile,
            'status' => CallStatus::COMPLETED->value,
            'disposition' => 'CALL_BACK',
            'initiated_by' => $agent2->id,
            'duration_seconds' => 90 + ($i * 20),
            'called_at' => now(),
            'ended_at' => now()->addSeconds(90 + ($i * 20)),
        ]);
    }

    CallLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'lead_id' => $lead2->id,
        'direction' => 'OUTBOUND',
        'telephony_provider' => 'EXOTEL',
        'from_number' => '08040000002',
        'to_number' => $lead2->mobile,
        'status' => CallStatus::NO_ANSWER->value,
        'disposition' => 'NOT_REACHABLE',
        'initiated_by' => $agent2->id,
        'duration_seconds' => 0,
        'called_at' => now(),
        'ended_at' => now(),
    ]);

    return [$institution, $manager, $agent1, $agent2, $lead1, $lead2];
}

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

// BRD: CRM-TC-007 — Call centre performance dashboard web access
it('displays call centre performance dashboard', function (): void {
    [$institution, $manager, $agent1, $agent2] = makeCallCentrePerformanceContext();

    $response = $this->actingAs($manager)->get(route('crm.communication.voice.performance', [
        'from_date' => now()->format('Y-m-d'),
        'to_date' => now()->format('Y-m-d'),
    ]));

    $response->assertOk()
        ->assertViewIs('crm.communication.voice.performance')
        ->assertViewHas('report');

    $report = $response->viewData('report');

    expect($report['summary']['total_calls_made'])->toBe(8)
        ->and($report['summary']['total_connects'])->toBe(5)
        ->and($report['summary']['total_conversions'])->toBe(1)
        ->and($report['summary']['agent_count'])->toBe(2);

    expect($report['per_agent'])->toHaveCount(2);
});

// BRD: CRM-TC-007 — Performance dashboard respects institution scoping
it('shows only institution scoped call data', function (): void {
    [$institution1, $manager1] = makeCallCentrePerformanceContext();

    $institution2 = Institution::create([
        'name' => 'Other Institute',
        'code' => 'OI',
        'is_active' => true,
    ]);

    $agent2 = User::create([
        'name' => 'Other Agent',
        'email' => 'other-agent@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution2->id,
    ]);

    $leadOther = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution2->id,
        'first_name' => 'External',
        'last_name' => 'Lead',
        'mobile' => '9876509999',
        'source' => 'website_organic',
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'temperature' => 'cold',
        'lead_score' => 50,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'call_consent_given' => true,
        'opt_out' => false,
        'assigned_counsellor_id' => $agent2->id,
    ]);

    CallLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution2->id,
        'lead_id' => $leadOther->id,
        'direction' => 'OUTBOUND',
        'telephony_provider' => 'EXOTEL',
        'from_number' => '08040000099',
        'to_number' => $leadOther->mobile,
        'status' => CallStatus::COMPLETED->value,
        'disposition' => 'INTERESTED',
        'initiated_by' => $agent2->id,
        'duration_seconds' => 300,
        'called_at' => now(),
        'ended_at' => now()->addSeconds(300),
    ]);

    $response = $this->actingAs($manager1)->get(route('crm.communication.voice.performance'));

    $response->assertOk();

    $report = $response->viewData('report');

    // Should NOT include institution2's call
    expect($report['summary']['total_calls_made'])->toBe(8)
        ->and($report['summary']['agent_count'])->toBe(2);
});

// BRD: CRM-TC-007 — API endpoint returns performance JSON
it('returns performance data via API endpoint', function (): void {
    [$institution, $manager] = makeCallCentrePerformanceContext();

    $response = $this->actingAs($manager)->getJson(route('api.v1.crm.voice.performance', [
        'from_date' => now()->format('Y-m-d'),
        'to_date' => now()->format('Y-m-d'),
    ]));

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'report' => [
                    'summary',
                    'per_agent',
                ],
                'volume_trend',
            ],
            'message',
        ]);

    expect($response->json('data.report.summary.total_calls_made'))->toBe(8)
        ->and($response->json('data.report.per_agent'))->toHaveCount(2);
});
