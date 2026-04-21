<?php

declare(strict_types=1);

use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Conversion Report API', function () {

    it('returns grouped conversion stats', function () {
        $institution = \App\Models\CRM\Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        Sanctum::actingAs($user, ['*']);

        $programme = CrmProgramme::factory()->for($institution)->create(['name' => 'MBA']);
        $lead = Lead::factory()->for($institution)->create(['source' => 'google_ads']);
        $application = Application::factory()->for($institution)->create([
            'lead_uuid' => $lead->uuid,
            'programme_id' => $programme->id,
            'assigned_counsellor_id' => $user->id,
        ]);
        ApplicationConversionLog::factory()->for($institution)->create([
            'application_uuid' => $application->uuid,
            'lead_uuid' => $lead->uuid,
            'status' => 'success',
            'completed_at' => now(),
        ]);

        $response = test()->getJson('/api/v1/crm/reports/conversion');
        $response->assertOk();
        $response->assertJsonStructure([
            'success', 'data', 'message', 'meta' => ['count']
        ]);
        expect($response->json('meta.count'))->toBe(1);
        expect($response->json('data.0.programme_name'))->toBe('MBA');
        expect($response->json('data.0.source'))->toBe('google_ads');
    });



    it('supports CSV export', function () {
        $institution = \App\Models\CRM\Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        Sanctum::actingAs($user, ['*']);
        $response = test()->get('/api/v1/crm/reports/conversion?export=csv');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    });

    it('supports XLSX export', function () {
        $institution = \App\Models\CRM\Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        Sanctum::actingAs($user, ['*']);
        $response = test()->get('/api/v1/crm/reports/conversion?export=xlsx');
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});
