<?php

declare(strict_types=1);

use App\Jobs\CRM\RunAnomalyDetectionJob;
use App\Models\CRM\AnomalyAlert;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('returns anomaly alerts for selected date', function (): void {
    $institution = Institution::create([
        'name' => 'Anomaly API Institute',
        'code' => 'AAI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Anomaly User',
        'email' => 'anomaly-api-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    AnomalyAlert::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'alert_type' => 'drop_off',
        'metric_name' => 'lead_volume',
        'current_value' => 6,
        'baseline_value' => 20,
        'deviation_percent' => -70.0,
        'threshold_percent' => 25,
        'severity' => 'critical',
        'rationale' => 'Detected significant lead volume drop.',
        'metadata' => ['window_days' => 7],
        'model_version' => 'a2a-anomaly-rules-v1',
        'detected_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/scoring/anomaly-alerts?for_date='.now()->toDateString());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.metric_name', 'lead_volume')
        ->assertJsonPath('data.0.severity', 'critical');
});

it('queues anomaly detection trigger endpoint', function (): void {
    Queue::fake();

    $institution = Institution::create([
        'name' => 'Anomaly Trigger Institute',
        'code' => 'ATI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Anomaly Trigger User',
        'email' => 'anomaly-trigger-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/scoring/anomaly-alerts/detect', [
            'for_date' => now()->toDateString(),
            'window_days' => 7,
            'baseline_days' => 28,
            'threshold_percent' => 25,
        ]);

    $response->assertStatus(202)
        ->assertJsonPath('success', true);

    Queue::assertPushed(RunAnomalyDetectionJob::class, function (RunAnomalyDetectionJob $job) use ($institution): bool {
        return $job->institutionId === $institution->id;
    });
});
