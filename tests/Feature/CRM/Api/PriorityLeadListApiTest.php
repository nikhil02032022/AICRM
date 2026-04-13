<?php

declare(strict_types=1);

use App\Models\CRM\CounsellorPriorityLead;
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

function makePriorityLeadUser(): array
{
    $institution = Institution::create([
        'name' => 'Priority API Institute',
        'code' => 'PAI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Priority User',
        'email' => 'priority@api.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'assigned_counsellor_id' => $user->id,
        'first_name' => 'Pia',
        'last_name' => 'Mehta',
        'mobile' => '9876543999',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 64,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    return [$user, $lead];
}

it('returns counsellor daily priority lead list', function (): void {
    [$user, $lead] = makePriorityLeadUser();

    CounsellorPriorityLead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'counsellor_id' => $user->id,
        'lead_id' => $lead->id,
        'priority_rank' => 1,
        'priority_score' => 82,
        'reasoning' => 'High score and inactivity make this lead a priority.',
        'factors' => ['lead_score' => 64, 'inactivity_days' => 7, 'conversion_probability' => 70],
        'generated_for_date' => now()->toDateString(),
        'generated_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/scoring/priority-leads')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.priority_rank', 1)
        ->assertJsonPath('data.0.priority_score', 82);
});

it('queues daily priority lead generation endpoint', function (): void {
    [$user] = makePriorityLeadUser();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/scoring/priority-leads/generate', ['for_date' => now()->toDateString()])
        ->assertStatus(202)
        ->assertJsonPath('success', true);
});
