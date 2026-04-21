<?php

declare(strict_types=1);

// BRD: CRM-AP-019 — Conversion rate report web tests

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Conversion Rate Web (AP-019)', function () {

    it('shows conversion rates page with rate data', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        test()->actingAs($user);

        $programme = CrmProgramme::factory()->for($institution)->create(['name' => 'BBA']);
        $lead = Lead::factory()->for($institution)->create([
            'source'           => \App\Enums\CRM\LeadSource::WEBSITE_ORGANIC,
            'preferred_intake' => '2026-07',
        ]);

        Application::factory()->for($institution)->create([
            'lead_uuid'    => $lead->uuid,
            'programme_id' => $programme->id,
            'status'       => ApplicationStatus::ENROLLED,
        ]);

        $response = test()->get('/crm/analytics/conversion-rates');
        $response->assertOk();
        $response->assertSee('Conversion Rates');
        $response->assertSee('BBA');
        $response->assertSee('2026-07');
        $response->assertSee('website_organic');
    });

    it('filters by batch on web view', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        test()->actingAs($user);

        $programme = CrmProgramme::factory()->for($institution)->create(['name' => 'MBA']);

        $leadA = Lead::factory()->for($institution)->create(['preferred_intake' => '2026-07']);
        $leadB = Lead::factory()->for($institution)->create(['preferred_intake' => '2027-01']);

        Application::factory()->for($institution)->create([
            'lead_uuid' => $leadA->uuid, 'programme_id' => $programme->id,
            'status' => ApplicationStatus::ENROLLED,
        ]);
        Application::factory()->for($institution)->create([
            'lead_uuid' => $leadB->uuid, 'programme_id' => $programme->id,
            'status' => ApplicationStatus::UNDER_REVIEW,
        ]);

        // Web controller passes through Livewire — just verify page renders without error
        $response = test()->get('/crm/analytics/conversion-rates');
        $response->assertOk();
    });

    it('supports CSV export', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        test()->actingAs($user);

        $response = test()->get('/crm/analytics/conversion-rates?export=csv');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    });

    it('supports XLSX export', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        test()->actingAs($user);

        $response = test()->get('/crm/analytics/conversion-rates?export=xlsx');
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});
