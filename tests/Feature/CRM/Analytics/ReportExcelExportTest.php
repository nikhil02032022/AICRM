<?php

declare(strict_types=1);

// BRD: CRM-AR-019 — Excel export feature tests for all 9 standard reports

use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('ReportExcelExport (CRM-AR-019)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.reports.export', 'guard_name' => 'web']);

        $directorRole = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $directorRole->givePermissionTo(['crm.reports.view', 'crm.reports.export']);

        // counsellor-activity query uses User::role([…]) — roles must exist even if no users have them
        Role::firstOrCreate(['name' => 'counsellor',        'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);

        $this->director = User::factory()->for($this->institution)->create();
        $this->director->assignRole('admissions_director');

        $this->expectedDate = now()->format('Ymd');
    });

    it('rejects unknown report type with 404', function () {
        $this->actingAs($this->director)
            ->get(route('crm.analytics.reports.export', ['report' => 'unknown-report', 'format' => 'excel']))
            ->assertNotFound();
    });

    it('rejects unsupported format with 422', function () {
        $this->actingAs($this->director)
            ->get(route('crm.analytics.reports.export', ['report' => 'enquiry-register', 'format' => 'csv']))
            ->assertStatus(422);
    });

    it('unauthenticated user is redirected', function () {
        $this->get(route('crm.analytics.reports.export', ['report' => 'enquiry-register', 'format' => 'excel']))
            ->assertRedirect();
    });

    it('user without crm.reports.export is forbidden', function () {
        $noExportRole = Role::firstOrCreate(['name' => 'no_export_role', 'guard_name' => 'web']);
        $noExportRole->givePermissionTo('crm.reports.view');

        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_export_role');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.export', ['report' => 'enquiry-register', 'format' => 'excel']))
            ->assertForbidden();
    });

    // ── per-report download assertions ───────────────────────────────────────

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
        it("downloads {$report} Excel with correct filename", function () use ($report) {
            Excel::fake();

            $this->actingAs($this->director)
                ->get(route('crm.analytics.reports.export', ['report' => $report, 'format' => 'excel']))
                ->assertOk();

            Excel::assertDownloaded("crm-report-{$report}-{$this->expectedDate}.xlsx");
        });
    }
});
