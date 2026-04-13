<?php

declare(strict_types=1);

use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\LeadNbaRecommendation;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeNbaLeadViewer(): array
{
    $institution = Institution::create([
        'name' => 'NBA API Institute',
        'code' => 'NBAI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'NBA User',
        'email' => 'nba@api.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Asha',
        'last_name' => 'Rao',
        'mobile' => '9876543001',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 61,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    return [$user, $lead];
}

it('returns latest nba recommendation snapshot for lead', function (): void {
    [$user, $lead] = makeNbaLeadViewer();

    LeadNbaRecommendation::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'lead_id' => $lead->id,
        'recommended_action' => 'send_whatsapp',
        'reasoning' => 'Recent engagement suggests quick WhatsApp follow-up.',
        'confidence_score' => 74,
        'channels' => ['whatsapp'],
        'metadata' => ['lead_score' => 61],
        'model_version' => 'a2a-nba-rules-v1',
        'generated_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/leads/'.$lead->uuid.'/next-best-action')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.recommended_action', 'send_whatsapp');
});

it('queues nba recommendation recalculation endpoint', function (): void {
    [$user, $lead] = makeNbaLeadViewer();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/leads/'.$lead->uuid.'/next-best-action/recalculate')
        ->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.lead_uuid', $lead->uuid);
});
