<?php

declare(strict_types=1);

use App\Models\CRM\ChurnFlag;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeChurnLeadViewer(): array
{
    $institution = Institution::create([
        'name' => 'Churn API Institute',
        'code' => 'CAI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Churn User',
        'email' => 'churn@api.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Ananya',
        'last_name' => 'Gupta',
        'mobile' => '9876543111',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'cold',
        'lead_score' => 42,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    return [$user, $lead];
}

it('returns latest churn risk snapshot for lead', function (): void {
    [$user, $lead] = makeChurnLeadViewer();

    ChurnFlag::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'lead_id' => $lead->id,
        'risk_level' => 'high',
        'risk_score' => 79,
        'rationale' => 'Lead shows low score and prolonged inactivity.',
        'indicators' => ['inactivity' => '14+ days'],
        'flagged_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/leads/'.$lead->uuid.'/churn-risk')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.risk_level', 'high')
        ->assertJsonPath('data.risk_score', 79);
});

it('queues churn risk recalculation endpoint', function (): void {
    [$user, $lead] = makeChurnLeadViewer();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/leads/'.$lead->uuid.'/churn-risk/recalculate')
        ->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.lead_uuid', $lead->uuid);
});
