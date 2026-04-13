<?php

declare(strict_types=1);

use App\Jobs\CRM\GenerateEnrolmentForecastJob;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\EnrolmentForecast;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('generates programme-wise enrolment forecast snapshots for institution', function (): void {
    $institution = Institution::create([
        'name' => 'Forecast Job Institute',
        'code' => 'FJI01',
        'is_active' => true,
    ]);

    $programme = CrmProgramme::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'name' => 'MBA',
        'code' => 'MBA001',
        'level' => 'PG',
        'department' => 'Management',
        'is_active' => true,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Forecast',
        'last_name' => 'Lead',
        'mobile' => '9876511100',
        'source' => 'walk_in',
        'status' => 'application_submitted',
        'temperature' => 'warm',
        'lead_score' => 70,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    $lead->programmeInterests()->attach($programme->id, ['is_primary' => true]);

    GenerateEnrolmentForecastJob::dispatchSync($institution->id, now()->startOfMonth()->toDateString());

    $rows = EnrolmentForecast::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->whereDate('generated_for_month', now()->startOfMonth()->toDateString())
        ->get();

    expect($rows)->toHaveCount(1);
    expect($rows->first()->forecast_count)->toBeGreaterThanOrEqual(0);
    expect($rows->first()->confidence_score)->toBeGreaterThanOrEqual(30);
});
