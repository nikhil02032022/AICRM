<?php

declare(strict_types=1);

use App\Events\CRM\LeadNbaRecommendedEvent;
use App\Jobs\CRM\RecalculateLeadNbaJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\LeadNbaRecommendation;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('creates next best action recommendation and dispatches event', function (): void {
    Event::fake([LeadNbaRecommendedEvent::class]);

    $institution = Institution::create([
        'name' => 'NBA Test Institute',
        'code' => 'NBI',
        'is_active' => true,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Dev',
        'last_name' => 'Nair',
        'mobile' => '9876501299',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'cold',
        'lead_score' => 48,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    RecalculateLeadNbaJob::dispatchSync($lead->uuid);

    $nba = LeadNbaRecommendation::withoutGlobalScopes()
        ->where('lead_id', $lead->id)
        ->latest('generated_at')
        ->first();

    expect($nba)->not->toBeNull();
    expect($nba->recommended_action)->not->toBe('');
    expect($nba->confidence_score)->toBeGreaterThan(0);

    Event::assertDispatched(LeadNbaRecommendedEvent::class, function (LeadNbaRecommendedEvent $event) use ($nba): bool {
        return $event->recommendation->id === $nba->id;
    });
});
