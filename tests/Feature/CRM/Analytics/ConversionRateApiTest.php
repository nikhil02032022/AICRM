<?php

declare(strict_types=1);

// BRD: CRM-AP-019 — Conversion rate report API tests

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Conversion Rate API (AP-019)', function () {

    it('returns conversion rate stats grouped by programme, batch, source, and counsellor', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        Sanctum::actingAs($user, ['*']);

        $programme = CrmProgramme::factory()->for($institution)->create(['name' => 'MBA']);
        $lead = Lead::factory()->for($institution)->create([
            'source'           => \App\Enums\CRM\LeadSource::GOOGLE_ADS,
            'preferred_intake' => '2026-07',
        ]);

        // 1 enrolled, 1 submitted total → rate = 50%
        Application::factory()->for($institution)->create([
            'lead_uuid'              => $lead->uuid,
            'programme_id'           => $programme->id,
            'assigned_counsellor_id' => $user->id,
            'status'                 => ApplicationStatus::ENROLLED,
        ]);
        Application::factory()->for($institution)->create([
            'lead_uuid'              => $lead->uuid,
            'programme_id'           => $programme->id,
            'assigned_counsellor_id' => $user->id,
            'status'                 => ApplicationStatus::SHORTLISTED,
        ]);

        $response = test()->getJson('/api/v1/crm/reports/conversion/rates');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'data', 'message', 'meta' => ['count'],
            ]);

        $data = $response->json('data.0');
        expect($data['programme_name'])->toBe('MBA');
        expect($data['batch'])->toBe('2026-07');
        expect($data['source'])->toBe('google_ads'); // LeadSource::GOOGLE_ADS->value
        expect($data['total_applications'])->toBe(2);
        expect($data['enrolled_count'])->toBe(1);
        expect((float) $data['conversion_rate'])->toBe(50.0);
    });

    it('filters by batch', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        Sanctum::actingAs($user, ['*']);

        $programme = CrmProgramme::factory()->for($institution)->create();

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

        $response = test()->getJson('/api/v1/crm/reports/conversion/rates?batch=2026-07');

        $response->assertOk();
        expect($response->json('meta.count'))->toBe(1);
        expect($response->json('data.0.batch'))->toBe('2026-07');
    });

    it('supports CSV export', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        Sanctum::actingAs($user, ['*']);

        $response = test()->get('/api/v1/crm/reports/conversion/rates?export=csv');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    });

    it('supports XLSX export', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->withRole('admin')->for($institution)->create();
        Sanctum::actingAs($user, ['*']);

        $response = test()->get('/api/v1/crm/reports/conversion/rates?export=xlsx');
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});
