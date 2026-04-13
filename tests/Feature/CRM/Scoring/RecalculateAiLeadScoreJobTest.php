<?php

declare(strict_types=1);

use App\Events\CRM\LeadAiScoreCalculatedEvent;
use App\Jobs\CRM\RecalculateAiLeadScoreJob;
use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->institution = Institution::create(['name' => 'AI Test Uni', 'code' => 'AIT', 'is_active' => true]);
});

it('creates ai lead score snapshot and dispatches event', function (): void {
    Event::fake();

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Amit',
        'last_name' => 'Shah',
        'mobile' => '9876543211',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'cold',
        'lead_score' => 42,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    RecalculateAiLeadScoreJob::dispatchSync($lead->uuid);

    $aiScore = AiLeadScore::withoutGlobalScopes()->where('lead_id', $lead->id)->latest('id')->first();

    expect($aiScore)->not->toBeNull()
        ->and($aiScore?->score)->toBeGreaterThanOrEqual(42)
        ->and($aiScore?->score)->toBeLessThanOrEqual(100)
        ->and($aiScore?->model_version)->not->toBe('');

    Event::assertDispatched(LeadAiScoreCalculatedEvent::class);
});
