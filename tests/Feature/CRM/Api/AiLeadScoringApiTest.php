<?php

declare(strict_types=1);

use App\Models\CRM\AiLeadScore;
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

function makeLeadViewer(): array
{
    $institution = Institution::create([
        'name' => 'Scoring API Institute',
        'code' => 'SAI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Scoring User',
        'email' => 'scoring@api.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Sonal',
        'last_name' => 'Mehra',
        'mobile' => '9876543222',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'cold',
        'lead_score' => 50,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    return [$user, $lead];
}

it('returns latest ai score snapshot for lead', function (): void {
    [$user, $lead] = makeLeadViewer();

    AiLeadScore::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'lead_id' => $lead->id,
        'score' => 68,
        'explanation' => 'AI detected stronger intent from qualification activity.',
        'model_version' => 'a2a-heuristic-v1',
        'metadata' => ['signal_count' => 3],
        'calculated_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/leads/'.$lead->uuid.'/ai-score')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.score', 68);
});

it('queues ai score recalculation endpoint', function (): void {
    [$user, $lead] = makeLeadViewer();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/leads/'.$lead->uuid.'/ai-score/recalculate')
        ->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.lead_uuid', $lead->uuid);
});
