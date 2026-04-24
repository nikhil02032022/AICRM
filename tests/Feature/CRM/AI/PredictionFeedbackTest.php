<?php

declare(strict_types=1);

// BRD: CRM-AI-001, CRM-AI-011 — Feature tests for prediction accept/reject feedback

use App\Models\CRM\AiLeadScore;
use App\Models\CRM\AiSuggestionDecision;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\AI\AiPredictionPermissionSeeder::class);

    $this->institution = Institution::factory()->create();

    $this->counsellor = User::factory()->create([
        'institution_id' => $this->institution->id,
    ]);
    $this->counsellor->assignRole('counsellor');

    $this->lead = Lead::factory()->create([
        'institution_id' => $this->institution->id,
    ]);

    // Seed an AI lead score with a completed prediction
    $this->score = AiLeadScore::withoutGlobalScopes()->create([
        'uuid'                    => (string) Str::uuid(),
        'institution_id'          => $this->institution->id,
        'lead_id'                 => $this->lead->id,
        'score'                   => 70,
        'explanation'             => 'Test prediction',
        'model_version'           => 'claude-sonnet-4-6',
        'metadata'                => [],
        'calculated_at'           => now(),
        'conversion_probability'  => 0.72,
        'confidence_score'        => 0.85,
        'prediction_factors'      => [['factor' => 'Test', 'weight' => 'positive', 'impact' => 'high']],
        'prediction_refreshed_at' => now(),
        'prediction_status'       => 'completed',
    ]);
});

it('counsellor can accept a conversion probability prediction', function (): void {
    $response = $this->actingAs($this->counsellor)
        ->post(route('crm.leads.ai-prediction.feedback', $this->lead->uuid), [
            'suggestion_uuid' => $this->score->uuid,
            'decision'        => 'accepted',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('ai_suggestion_decisions', [
        'lead_id'         => $this->lead->id,
        'suggestion_type' => 'conversion_prediction',
        'suggestion_uuid' => $this->score->uuid,
        'decision'        => 'accepted',
        'acted_by'        => $this->counsellor->id,
    ]);
});

it('counsellor can reject a conversion probability prediction', function (): void {
    $response = $this->actingAs($this->counsellor)
        ->post(route('crm.leads.ai-prediction.feedback', $this->lead->uuid), [
            'suggestion_uuid' => $this->score->uuid,
            'decision'        => 'rejected',
            'notes'           => 'Lead has already enrolled elsewhere',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('ai_suggestion_decisions', [
        'suggestion_type' => 'conversion_prediction',
        'decision'        => 'rejected',
        'notes'           => 'Lead has already enrolled elsewhere',
    ]);
});

it('unauthorised user cannot submit prediction feedback', function (): void {
    $otherInstitution = Institution::factory()->create();
    $otherUser        = User::factory()->create(['institution_id' => $otherInstitution->id]);
    $otherUser->assignRole('counsellor');

    $response = $this->actingAs($otherUser)
        ->post(route('crm.leads.ai-prediction.feedback', $this->lead->uuid), [
            'suggestion_uuid' => $this->score->uuid,
            'decision'        => 'accepted',
        ]);

    // InstitutionScope hides cross-tenant leads entirely (404), which is correct multi-tenant behavior
    $response->assertNotFound();
});
