<?php

declare(strict_types=1);

use App\Enums\CRM\ChurnRiskLevel;
use App\Events\CRM\LeadChurnFlaggedEvent;
use App\Jobs\CRM\RecalculateLeadChurnRiskJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('creates churn flag snapshot and dispatches event', function (): void {
    Event::fake([LeadChurnFlaggedEvent::class]);

    $institution = Institution::create([
        'name' => 'Churn Test Institute',
        'code' => 'CTI',
        'is_active' => true,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Riya',
        'last_name' => 'Kapoor',
        'mobile' => '9876501234',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'cold',
        'lead_score' => 34,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'updated_at' => now()->subDays(16),
    ]);

    (new RecalculateLeadChurnRiskJob($lead->uuid))->handle(app(\App\Services\CRM\AI\ChurnDetectionService::class));

    $churnFlag = $lead->fresh()->churnFlags()->latest('flagged_at')->first();

    expect($churnFlag)->not->toBeNull();
    expect($churnFlag->risk_level)->toBe(ChurnRiskLevel::HIGH);
    expect($churnFlag->risk_score)->toBeGreaterThanOrEqual(70);

    Event::assertDispatched(LeadChurnFlaggedEvent::class, function (LeadChurnFlaggedEvent $event) use ($churnFlag): bool {
        return $event->churnFlag->id === $churnFlag->id;
    });
});
