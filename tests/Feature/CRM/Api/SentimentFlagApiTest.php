<?php

declare(strict_types=1);

use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\SentimentFlag;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeSentimentLeadViewer(): array
{
    $institution = Institution::create([
        'name' => 'Sentiment API Institute',
        'code' => 'SAI2',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Sentiment User',
        'email' => 'sentiment@api.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Rhea',
        'last_name' => 'Kapoor',
        'mobile' => '9876509870',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 55,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    return [$user, $lead];
}

it('returns latest sentiment snapshot for lead', function (): void {
    [$user, $lead] = makeSentimentLeadViewer();

    SentimentFlag::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $lead->institution_id,
        'lead_id' => $lead->id,
        'channel' => 'whatsapp',
        'sentiment_label' => 'negative',
        'sentiment_score' => -41,
        'is_urgent' => true,
        'rationale' => 'Detected urgent negative language in latest inbound communication.',
        'source_excerpt' => 'I am unhappy and need this resolved urgently.',
        'indicators' => ['negative_hits' => 2, 'urgent_hits' => 1, 'positive_hits' => 0],
        'model_version' => 'a2a-sentiment-rules-v1',
        'flagged_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/leads/'.$lead->uuid.'/sentiment')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.sentiment_label', 'negative')
        ->assertJsonPath('data.is_urgent', true);
});

it('queues sentiment recalculation endpoint', function (): void {
    [$user, $lead] = makeSentimentLeadViewer();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/leads/'.$lead->uuid.'/sentiment/recalculate')
        ->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.lead_uuid', $lead->uuid);
});
