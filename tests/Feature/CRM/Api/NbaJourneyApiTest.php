<?php

declare(strict_types=1);

use App\Jobs\CRM\GenerateNbaJourneyJob;
use App\Models\CRM\Institution;
use App\Models\CRM\NbaJourney;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('returns nurture journey suggestions for selected date', function (): void {
    $institution = Institution::create([
        'name' => 'Journey API Institute',
        'code' => 'JAI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Journey User',
        'email' => 'journey-api-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    NbaJourney::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'segment_key' => 'warm_leads',
        'segment_label' => 'Warm Leads',
        'confidence_score' => 82,
        'rationale' => 'Warm segment needs follow-up cadence.',
        'steps' => [
            ['day_offset' => 0, 'channel' => 'email', 'action' => 'Share programme fit story'],
        ],
        'metadata' => ['lead_count' => 34],
        'model_version' => 'a2a-nba-journey-rules-v1',
        'generated_for_date' => now()->toDateString(),
        'suggested_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/scoring/nba-journeys?for_date='.now()->toDateString());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.segment_key', 'warm_leads')
        ->assertJsonPath('data.0.confidence_score', 82);
});

it('queues nurture journey generation endpoint', function (): void {
    Queue::fake();

    $institution = Institution::create([
        'name' => 'Journey Trigger Institute',
        'code' => 'JTI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Journey Trigger User',
        'email' => 'journey-trigger-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/scoring/nba-journeys/generate', [
            'for_date' => now()->toDateString(),
            'segment' => 'hot_leads',
        ]);

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.segment', 'hot_leads');

    Queue::assertPushed(GenerateNbaJourneyJob::class, function (GenerateNbaJourneyJob $job) use ($institution): bool {
        return $job->institutionId === $institution->id
            && $job->segment === 'hot_leads';
    });
});
