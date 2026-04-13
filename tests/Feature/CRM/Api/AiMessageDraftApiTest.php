<?php

declare(strict_types=1);

use App\Models\CRM\AiMessageDraft;
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

function makeDraftLeadUser(): array
{
    $institution = Institution::create([
        'name' => 'Draft API Institute',
        'code' => 'DAI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Draft User',
        'email' => 'draft@api.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.communication.send']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Nikhil',
        'last_name' => 'Roy',
        'mobile' => '9876543555',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 62,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    return [$user, $lead];
}

it('returns latest ai message draft for lead and channel', function (): void {
    [$user, $lead] = makeDraftLeadUser();

    AiMessageDraft::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'lead_id' => $lead->id,
        'channel' => 'email',
        'subject' => 'Admission next steps',
        'draft_text' => 'Hello and welcome.',
        'model_version' => 'a2a-draft-rules-v1',
        'generated_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/leads/'.$lead->uuid.'/ai-drafts?channel=email')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.channel', 'email');
});

it('queues ai draft generation endpoint', function (): void {
    [$user, $lead] = makeDraftLeadUser();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/leads/'.$lead->uuid.'/ai-drafts/generate', ['channel' => 'whatsapp'])
        ->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.channel', 'whatsapp');
});
