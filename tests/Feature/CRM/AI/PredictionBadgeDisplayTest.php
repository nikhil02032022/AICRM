<?php

declare(strict_types=1);

// BRD: CRM-AI-001 — Feature tests for conversion probability badge display on lead views

use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use App\Livewire\CRM\Lead\ConversionProbabilityBadge;

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
});

it('renders conversion probability badge on lead detail page with completed prediction', function (): void {
    AiLeadScore::withoutGlobalScopes()->create([
        'uuid'                    => (string) Str::uuid(),
        'institution_id'          => $this->institution->id,
        'lead_id'                 => $this->lead->id,
        'score'                   => 70,
        'explanation'             => 'Test',
        'model_version'           => 'claude-sonnet-4-6',
        'metadata'                => [],
        'calculated_at'           => now(),
        'conversion_probability'  => 0.65,
        'confidence_score'        => 0.78,
        'prediction_factors'      => [
            ['factor' => 'Active sessions', 'weight' => 'positive', 'impact' => 'high'],
            ['factor' => 'Doc incomplete',  'weight' => 'negative', 'impact' => 'medium'],
            ['factor' => 'Good source',     'weight' => 'positive', 'impact' => 'low'],
        ],
        'prediction_refreshed_at' => now(),
        'prediction_status'       => 'completed',
    ]);

    Livewire::actingAs($this->counsellor)
        ->test(ConversionProbabilityBadge::class, ['leadUuid' => $this->lead->uuid])
        ->assertSee('65.0%')
        ->assertSee('Conversion Probability');
});

it('shows insufficient data message when confidence is below threshold', function (): void {
    AiLeadScore::withoutGlobalScopes()->create([
        'uuid'                    => (string) Str::uuid(),
        'institution_id'          => $this->institution->id,
        'lead_id'                 => $this->lead->id,
        'score'                   => 40,
        'explanation'             => 'Low confidence test',
        'model_version'           => 'claude-sonnet-4-6',
        'metadata'                => [],
        'calculated_at'           => now(),
        'conversion_probability'  => 0.50,
        'confidence_score'        => 0.18, // Below 0.30 threshold
        'prediction_factors'      => [['factor' => 'Insufficient data', 'weight' => 'neutral', 'impact' => 'low']],
        'prediction_refreshed_at' => now(),
        'prediction_status'       => 'completed',
    ]);

    Livewire::actingAs($this->counsellor)
        ->test(ConversionProbabilityBadge::class, ['leadUuid' => $this->lead->uuid])
        ->assertSee('Insufficient data');
});
