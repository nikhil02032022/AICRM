<?php

declare(strict_types=1);

use App\Events\CRM\AiSuggestionDecisionRecordedEvent;
use App\Models\CRM\AiSuggestionDecision;
use App\Models\CRM\AiUsageLog;
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

it('records ai usage log entry when ai suggestion decision event is fired', function (): void {
    $institution = Institution::create([
        'name' => 'AI Usage Event Institute',
        'code' => 'AUE01',
        'is_active' => true,
    ]);

    $actor = User::create([
        'name' => 'AI Auditor',
        'email' => 'ai-auditor@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Arun',
        'last_name' => 'Paul',
        'mobile' => '9876507788',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 66,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    $decision = AiSuggestionDecision::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'suggestion_type' => 'next_best_action',
        'suggestion_uuid' => (string) Str::uuid(),
        'decision' => 'accepted',
        'acted_by' => $actor->id,
        'acted_at' => now(),
    ]);

    AiSuggestionDecisionRecordedEvent::dispatch($decision);

    $log = AiUsageLog::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('reference_uuid', $decision->uuid)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect($log?->feature_key)->toBe('human_decision');
    expect($log?->action)->toBe('accepted');
    expect($log?->actor_id)->toBe($actor->id);
});
