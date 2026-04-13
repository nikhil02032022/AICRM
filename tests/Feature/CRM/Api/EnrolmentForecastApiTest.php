<?php

declare(strict_types=1);

use App\Jobs\CRM\GenerateEnrolmentForecastJob;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\EnrolmentForecast;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('returns programme-wise enrolment forecasts for selected month', function (): void {
    $institution = Institution::create([
        'name' => 'Forecast API Institute',
        'code' => 'FAI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Forecast User',
        'email' => 'forecast-api-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $programme = CrmProgramme::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'name' => 'BBA',
        'code' => 'BBA001',
        'level' => 'UG',
        'department' => 'Management',
        'is_active' => true,
    ]);

    EnrolmentForecast::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'crm_programme_id' => $programme->id,
        'admission_cycle' => now()->format('Y'),
        'forecast_count' => 42,
        'confidence_score' => 78,
        'inputs' => [
            'pipeline_ready' => 50,
            'enrolled' => 15,
            'momentum' => 1.2,
        ],
        'generated_for_month' => now()->startOfMonth()->toDateString(),
        'generated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/scoring/enrolment-forecasts?for_month='.now()->format('Y-m'));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.programme_name', 'BBA')
        ->assertJsonPath('data.0.forecast_count', 42);
});

it('queues enrolment forecast generation for selected month', function (): void {
    Queue::fake();

    $institution = Institution::create([
        'name' => 'Forecast Trigger Institute',
        'code' => 'FTI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Forecast Trigger User',
        'email' => 'forecast-trigger-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    $forMonth = now()->format('Y-m');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/scoring/enrolment-forecasts/generate', [
            'for_month' => $forMonth,
        ]);

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.for_month', $forMonth);

    Queue::assertPushed(GenerateEnrolmentForecastJob::class, function (GenerateEnrolmentForecastJob $job) use ($institution): bool {
        return $job->institutionId === $institution->id;
    });
});
