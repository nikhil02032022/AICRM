<?php

declare(strict_types=1);

use App\Models\CRM\AiSuggestionDecision;
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

it('records accepted ai suggestion decision', function (): void {
    $institution = Institution::create([
        'name' => 'Decision API Institute',
        'code' => 'DAI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Decision User',
        'email' => 'decision-api-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Isha',
        'last_name' => 'Patel',
        'mobile' => '9876512299',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 62,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    $suggestionUuid = (string) Str::uuid();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/scoring/ai-suggestions/decision', [
            'lead_uuid' => $lead->uuid,
            'suggestion_type' => 'next_best_action',
            'suggestion_uuid' => $suggestionUuid,
            'decision' => 'accepted',
            'notes' => 'Counsellor approved recommendation.',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.suggestion_type', 'next_best_action')
        ->assertJsonPath('data.decision', 'accepted')
        ->assertJsonPath('data.lead_uuid', $lead->uuid);

    $stored = AiSuggestionDecision::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('lead_id', $lead->id)
        ->latest('id')
        ->first();

    expect($stored)->not->toBeNull();
    expect($stored?->suggestion_uuid)->toBe($suggestionUuid);
    expect($stored?->decision)->toBe('accepted');
    expect($stored?->acted_by)->toBe($user->id);
});

it('requires edited content when decision is edited', function (): void {
    $institution = Institution::create([
        'name' => 'Decision Validation Institute',
        'code' => 'DVI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Decision Validation User',
        'email' => 'decision-validation-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/scoring/ai-suggestions/decision', [
            'suggestion_type' => 'message_draft',
            'decision' => 'edited',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['edited_content']);
});
