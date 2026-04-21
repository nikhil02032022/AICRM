<?php

declare(strict_types=1);

use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('Conversion Report Web', function () {

    it('shows conversion report page', function () {
        $institution = \App\Models\CRM\Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        test()->actingAs($user);

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

        $response = test()->get('/crm/analytics/conversion-report');
        $response->assertOk();
        $response->assertSee('Conversion Report');
        $response->assertSee('MBA');
        $response->assertSee('google_ads');
    });



    it('supports CSV export', function () {
        $institution = \App\Models\CRM\Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        test()->actingAs($user);
        $response = test()->get('/crm/analytics/conversion-report?export=csv');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    });

    it('supports XLSX export', function () {
        $institution = \App\Models\CRM\Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        test()->actingAs($user);
        $response = test()->get('/crm/analytics/conversion-report?export=xlsx');
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});
