<?php

declare(strict_types=1);

// BRD: CRM-AR-019 — PDF export feature tests for all 9 standard reports

use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('ReportPdfExport (CRM-AR-019)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.reports.export', 'guard_name' => 'web']);

        $directorRole = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $directorRole->givePermissionTo(['crm.reports.view', 'crm.reports.export']);

        // counsellor-activity query uses User::role([…]) — roles must exist even with no users
        Role::firstOrCreate(['name' => 'counsellor',        'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);

        $this->director = User::factory()->for($this->institution)->create();
        $this->director->assignRole('admissions_director');
    });

    $reports = [
        'enquiry-register',
        'counsellor-activity',
        'application-status',
        'source-effectiveness',
        'lost-lead-analysis',
        'fee-collection',
        'document-compliance',
        'year-on-year',
        'agent-performance',
    ];

    foreach ($reports as $report) {
        it("downloads {$report} PDF with correct Content-Type", function () use ($report) {
            $response = $this->actingAs($this->director)
                ->get(route('crm.analytics.reports.export', ['report' => $report, 'format' => 'pdf']));

            $response->assertOk()
                ->assertHeader('Content-Type', 'application/pdf');
        });
    }

    it('PDF response includes Content-Disposition attachment header', function () {
        $response = $this->actingAs($this->director)
            ->get(route('crm.analytics.reports.export', ['report' => 'enquiry-register', 'format' => 'pdf']));

        $response->assertOk();

        $disposition = $response->headers->get('Content-Disposition');
        expect($disposition)->toContain('attachment')
            ->and($disposition)->toContain('crm-report-enquiry-register')
            ->and($disposition)->toContain('.pdf');
    });
});
