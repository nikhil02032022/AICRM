<?php

declare(strict_types=1);

use App\Jobs\CRM\RunAnomalyDetectionJob;
use App\Models\CRM\AnomalyAlert;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\AnomalyDetectionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('detects drop-off anomaly alerts for lead volume against baseline', function (): void {
    $institution = Institution::create([
        'name' => 'Anomaly Job Institute',
        'code' => 'AJI01',
        'is_active' => true,
    ]);

    for ($i = 0; $i < 24; $i++) {
        Lead::withoutGlobalScopes()->forceCreate([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'first_name' => 'Baseline',
            'last_name' => 'Lead'.$i,
            'mobile' => '987652'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'source' => 'walk_in',
            'status' => 'new_enquiry',
            'temperature' => 'warm',
            'lead_score' => 55,
            'consent_given' => true,
            'consent_timestamp' => now(),
            'consent_form_version' => 'v1',
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);
    }

    for ($i = 0; $i < 4; $i++) {
        Lead::withoutGlobalScopes()->forceCreate([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'first_name' => 'Current',
            'last_name' => 'Lead'.$i,
            'mobile' => '987653'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'source' => 'walk_in',
            'status' => 'new_enquiry',
            'temperature' => 'cold',
            'lead_score' => 40,
            'consent_given' => true,
            'consent_timestamp' => now(),
            'consent_form_version' => 'v1',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }

    $job = new RunAnomalyDetectionJob($institution->id, now()->toDateString(), 7, 28, 25);
    $job->handle(app(AnomalyDetectionService::class));

    $alerts = AnomalyAlert::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->get();

    expect($alerts->count())->toBeGreaterThan(0);
    expect($alerts->first()->alert_type)->toBe('drop_off');
    expect($alerts->first()->deviation_percent)->toBeLessThanOrEqual(-25.0);
});
