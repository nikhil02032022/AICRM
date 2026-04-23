<?php

declare(strict_types=1);

// BRD: CRM-AR-009 to CRM-AR-017 — Standard reports feature tests

use App\Models\CRM\Application;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionAccrual;
use App\Models\CRM\Campus;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('CounsellorActivityReport (CRM-AR-010)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',    'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.analytics.view',  'guard_name' => 'web']);

        $directorRole  = Role::firstOrCreate(['name' => 'admissions_director',  'guard_name' => 'web']);
        $managerRole   = Role::firstOrCreate(['name' => 'admissions_manager',   'guard_name' => 'web']);
        $counsellorRole = Role::firstOrCreate(['name' => 'counsellor',          'guard_name' => 'web']);

        $directorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
        $managerRole->givePermissionTo(['crm.reports.view',  'crm.analytics.view']);
        $counsellorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
    });

    it('director can access counsellor activity report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.counsellor-activity'));

        $response->assertOk()
            ->assertSee('Counsellor Activity Report');
    });

    it('manager can access counsellor activity report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_manager');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.counsellor-activity'));

        $response->assertOk();
    });

    it('counsellor can access own activity report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('counsellor');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.counsellor-activity'));

        $response->assertOk();
    });

    it('unauthenticated user is redirected', function () {
        $response = $this->get(route('crm.analytics.reports.counsellor-activity'));

        $response->assertRedirect();
    });

    it('user without crm.reports.view permission is forbidden', function () {
        $role = Role::firstOrCreate(['name' => 'no_reports_role', 'guard_name' => 'web']);
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_reports_role');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.counsellor-activity'));

        $response->assertForbidden();
    });

    it('report shows counsellor row with new_leads count', function () {
        $campus = Campus::factory()->for($this->institution)->create();

        $counsellor = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $counsellor->assignRole('counsellor');

        // Create 3 leads assigned to counsellor in current month
        Lead::factory(3)->for($this->institution)->create([
            'assigned_counsellor_id' => $counsellor->id,
            'campus_id'              => $campus->id,
            'created_at'             => now()->startOfMonth()->addDays(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.counsellor-activity', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->contains('id', $counsellor->id))
            ->assertViewHas('rows', fn ($rows) => $rows->firstWhere('id', $counsellor->id)?->new_leads == 3);
    });

    it('director scope returns all institution counsellors', function () {
        $c1 = User::factory()->for($this->institution)->create();
        $c1->assignRole('counsellor');
        $c2 = User::factory()->for($this->institution)->create();
        $c2->assignRole('counsellor');

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.counsellor-activity'));

        $response->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->contains('id', $c1->id) && $rows->contains('id', $c2->id));
    });

    it('counsellor scope returns only own row', function () {
        $counsellor = User::factory()->for($this->institution)->create();
        $counsellor->assignRole('counsellor');

        $other = User::factory()->for($this->institution)->create();
        $other->assignRole('counsellor');

        $response = $this->actingAs($counsellor)
            ->get(route('crm.analytics.reports.counsellor-activity'));

        $response->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->contains('id', $counsellor->id))
            ->assertViewHas('rows', fn ($rows) => ! $rows->contains('id', $other->id));
    });

    it('date range filter is reflected in view', function () {
        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.counsellor-activity', [
                'from' => '2026-03-01',
                'to'   => '2026-03-31',
            ]));

        $response->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['from'] === '2026-03-01' && $f['to'] === '2026-03-31');
    });

    it('tasks_completed count reflects completed tasks in period', function () {
        $counsellor = User::factory()->for($this->institution)->create();
        $counsellor->assignRole('counsellor');

        $campus = Campus::factory()->for($this->institution)->create();
        $lead   = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        Task::factory(2)->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'assigned_to'    => $counsellor->id,
            'lead_id'        => $lead->id,
            'status'         => 'completed',
            'completed_at'   => now()->startOfMonth()->addDays(3),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.counsellor-activity', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('rows', fn ($rows) =>
                $rows->firstWhere('id', $counsellor->id)?->tasks_completed == 2
            );
    });
});

describe('ApplicationStatusReport (CRM-AR-011)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.analytics.view', 'guard_name' => 'web']);

        $directorRole   = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $counsellorRole = Role::firstOrCreate(['name' => 'counsellor',          'guard_name' => 'web']);

        $directorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
        $counsellorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
    });

    it('director can access application status report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.application-status'));

        $response->assertOk()
            ->assertSee('Application Status Report');
    });

    it('unauthenticated user is redirected', function () {
        $response = $this->get(route('crm.analytics.reports.application-status'));

        $response->assertRedirect();
    });

    it('user without crm.reports.view permission is forbidden', function () {
        $role = Role::firstOrCreate(['name' => 'no_reports_role2', 'guard_name' => 'web']);
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_reports_role2');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.application-status'));

        $response->assertForbidden();
    });

    it('date range filter is reflected in view', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.application-status', [
                'from' => '2026-02-01',
                'to'   => '2026-02-28',
            ]));

        $response->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['from'] === '2026-02-01' && $f['to'] === '2026-02-28');
    });

    it('report includes applications submitted in period', function () {
        $campus     = Campus::factory()->for($this->institution)->create();
        $counsellor = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $counsellor->assignRole('counsellor');

        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $app = Application::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'lead_uuid'              => $lead->uuid,
            'assigned_counsellor_id' => $counsellor->id,
            'submitted_at'           => now()->startOfMonth()->addDays(5),
            'status'                 => 'under_review',
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.application-status', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('applications', fn ($p) => $p->contains('uuid', $app->uuid));
    });

    it('counsellor scope sees only own applications', function () {
        $campus = Campus::factory()->for($this->institution)->create();
        $lead   = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $counsellor = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $counsellor->assignRole('counsellor');

        $other = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $other->assignRole('counsellor');

        $ownApp = Application::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'lead_uuid'              => $lead->uuid,
            'assigned_counsellor_id' => $counsellor->id,
            'submitted_at'           => now()->startOfMonth()->addDays(1),
        ]);

        $otherApp = Application::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'lead_uuid'              => $lead->uuid,
            'assigned_counsellor_id' => $other->id,
            'submitted_at'           => now()->startOfMonth()->addDays(1),
        ]);

        $response = $this->actingAs($counsellor)
            ->get(route('crm.analytics.reports.application-status', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('applications', fn ($p) =>  $p->contains('uuid', $ownApp->uuid))
            ->assertViewHas('applications', fn ($p) => !$p->contains('uuid', $otherApp->uuid));
    });

    it('status filter narrows results', function () {
        $campus = Campus::factory()->for($this->institution)->create();
        $lead   = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        Application::factory()->for($this->institution)->create([
            'campus_id'    => $campus->id,
            'lead_uuid'    => $lead->uuid,
            'status'       => 'enrolled',
            'submitted_at' => now()->startOfMonth()->addDays(2),
        ]);
        Application::factory()->for($this->institution)->create([
            'campus_id'    => $campus->id,
            'lead_uuid'    => $lead->uuid,
            'status'       => 'under_review',
            'submitted_at' => now()->startOfMonth()->addDays(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.application-status', [
                'from'   => now()->startOfMonth()->toDateString(),
                'to'     => now()->toDateString(),
                'status' => 'enrolled',
            ]));

        $response->assertOk()
            ->assertViewHas('applications', fn ($p) =>
                $p->every(fn ($a) => $a->status->value === 'enrolled')
            );
    });
});

describe('SourceEffectivenessReport (CRM-AR-012)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.analytics.view', 'guard_name' => 'web']);

        $directorRole   = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $counsellorRole = Role::firstOrCreate(['name' => 'counsellor',          'guard_name' => 'web']);

        $directorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
        $counsellorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
    });

    it('director can access source effectiveness report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.source-effectiveness'));

        $response->assertOk()
            ->assertSee('Source Effectiveness Report');
    });

    it('unauthenticated user is redirected', function () {
        $response = $this->get(route('crm.analytics.reports.source-effectiveness'));

        $response->assertRedirect();
    });

    it('user without permission is forbidden', function () {
        $role = Role::firstOrCreate(['name' => 'no_reports_role3', 'guard_name' => 'web']);
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_reports_role3');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.source-effectiveness'));

        $response->assertForbidden();
    });

    it('date range filter is reflected in view', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.source-effectiveness', [
                'from' => '2026-01-01',
                'to'   => '2026-01-31',
            ]));

        $response->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['from'] === '2026-01-01' && $f['to'] === '2026-01-31');
    });

    it('report groups leads by source and counts correctly', function () {
        $campus = Campus::factory()->for($this->institution)->create();

        // 3 google_ads leads in period
        Lead::factory(3)->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'source'     => 'google_ads',
            'created_at' => now()->startOfMonth()->addDays(2),
        ]);
        // 1 facebook lead in period
        Lead::factory()->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'source'     => 'facebook',
            'created_at' => now()->startOfMonth()->addDays(2),
        ]);
        // 1 google_ads lead outside period — should not be counted
        Lead::factory()->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'source'     => 'google_ads',
            'created_at' => now()->subMonths(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.source-effectiveness', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('rows', fn ($rows) =>
                $rows->firstWhere('source', 'google_ads')?->total_leads == 3
            )
            ->assertViewHas('rows', fn ($rows) =>
                $rows->firstWhere('source', 'facebook')?->total_leads == 1
            );
    });

    it('enrolled count reflects converted leads', function () {
        $campus = Campus::factory()->for($this->institution)->create();

        Lead::factory(2)->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'source'     => 'walk_in',
            'status'     => 'enrolled',
            'created_at' => now()->startOfMonth()->addDays(1),
        ]);
        Lead::factory()->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'source'     => 'walk_in',
            'status'     => 'new_enquiry',
            'created_at' => now()->startOfMonth()->addDays(1),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.source-effectiveness', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('rows', fn ($rows) =>
                $rows->firstWhere('source', 'walk_in')?->enrolled == 2
            )
            ->assertViewHas('rows', fn ($rows) =>
                $rows->firstWhere('source', 'walk_in')?->total_leads == 3
            );
    });

    it('counsellor scope returns only own-lead sources (AR-012)', function () {
        $campus     = Campus::factory()->for($this->institution)->create();
        $counsellor = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $counsellor->assignRole('counsellor');

        Lead::factory(2)->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'source'                 => 'referral',
            'assigned_counsellor_id' => $counsellor->id,
            'created_at'             => now()->startOfMonth()->addDays(1),
        ]);
        // Lead assigned to another counsellor — same source — should not inflate count
        Lead::factory()->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'source'     => 'referral',
            'created_at' => now()->startOfMonth()->addDays(1),
        ]);

        $response = $this->actingAs($counsellor)
            ->get(route('crm.analytics.reports.source-effectiveness', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('rows', fn ($rows) =>
                $rows->firstWhere('source', 'referral')?->total_leads == 2
            );
    });
});

describe('LostLeadAnalysisReport (CRM-AR-013)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.analytics.view', 'guard_name' => 'web']);

        $directorRole        = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $counsellorRole      = Role::firstOrCreate(['name' => 'counsellor',          'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);

        $directorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
        $counsellorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
    });

    it('director can access lost lead analysis report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.lost-lead-analysis'));

        $response->assertOk()
            ->assertSee('Lost Lead Analysis Report');
    });

    it('unauthenticated user is redirected', function () {
        $response = $this->get(route('crm.analytics.reports.lost-lead-analysis'));

        $response->assertRedirect();
    });

    it('user without crm.reports.view permission is forbidden', function () {
        $role = Role::firstOrCreate(['name' => 'no_reports_role4', 'guard_name' => 'web']);
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_reports_role4');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.lost-lead-analysis'));

        $response->assertForbidden();
    });

    it('date range filter is reflected in view', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)
            ->get(route('crm.analytics.reports.lost-lead-analysis', [
                'from' => '2026-03-01',
                'to'   => '2026-03-31',
            ]));

        $response->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['from'] === '2026-03-01' && $f['to'] === '2026-03-31');
    });

    it('only leads with status=lost and status_changed_at in period are returned', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'Test Campus',
            'code'           => 'TC1',
            'is_active'      => true,
        ]);

        $lost = Lead::factory()->for($this->institution)->create([
            'campus_id'         => $campus->id,
            'status'            => 'lost',
            'status_changed_at' => now()->startOfMonth()->addDays(5),
        ]);

        // Lead with status=lost but outside period
        Lead::factory()->for($this->institution)->create([
            'campus_id'         => $campus->id,
            'status'            => 'lost',
            'status_changed_at' => now()->subMonths(2),
        ]);

        // Lead in period but not lost
        Lead::factory()->for($this->institution)->create([
            'campus_id'         => $campus->id,
            'status'            => 'contacted',
            'status_changed_at' => now()->startOfMonth()->addDays(3),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.lost-lead-analysis', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('leads', fn ($p) =>  $p->contains('uuid', $lost->uuid))
            ->assertViewHas('leads', fn ($p) => $p->total() === 1);
    });

    it('counsellor scope sees only own lost leads', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'Test Campus',
            'code'           => 'TC2',
            'is_active'      => true,
        ]);

        $counsellor = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $counsellor->assignRole('counsellor');

        $other = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $other->assignRole('counsellor');

        $ownLost = Lead::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'status'                 => 'lost',
            'status_changed_at'      => now()->startOfMonth()->addDays(2),
            'assigned_counsellor_id' => $counsellor->id,
        ]);

        $otherLost = Lead::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'status'                 => 'lost',
            'status_changed_at'      => now()->startOfMonth()->addDays(2),
            'assigned_counsellor_id' => $other->id,
        ]);

        $response = $this->actingAs($counsellor)
            ->get(route('crm.analytics.reports.lost-lead-analysis', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('leads', fn ($p) =>  $p->contains('uuid', $ownLost->uuid))
            ->assertViewHas('leads', fn ($p) => !$p->contains('uuid', $otherLost->uuid));
    });

    it('lost_reason filter narrows paginated results', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'Test Campus',
            'code'           => 'TC3',
            'is_active'      => true,
        ]);

        Lead::factory()->for($this->institution)->create([
            'campus_id'         => $campus->id,
            'status'            => 'lost',
            'lost_reason'       => 'no_response',
            'status_changed_at' => now()->startOfMonth()->addDays(3),
        ]);
        Lead::factory()->for($this->institution)->create([
            'campus_id'         => $campus->id,
            'status'            => 'lost',
            'lost_reason'       => 'financial_constraint',
            'status_changed_at' => now()->startOfMonth()->addDays(3),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.lost-lead-analysis', [
                'from'        => now()->startOfMonth()->toDateString(),
                'to'          => now()->toDateString(),
                'lost_reason' => 'no_response',
            ]));

        $response->assertOk()
            ->assertViewHas('leads', fn ($p) =>
                $p->every(fn ($l) => $l->lost_reason?->value === 'no_response')
            );
    });

    it('reason summary groups and counts correctly', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'Test Campus',
            'code'           => 'TC4',
            'is_active'      => true,
        ]);

        Lead::factory(3)->for($this->institution)->create([
            'campus_id'         => $campus->id,
            'status'            => 'lost',
            'lost_reason'       => 'no_response',
            'status_changed_at' => now()->startOfMonth()->addDays(2),
        ]);
        Lead::factory(2)->for($this->institution)->create([
            'campus_id'         => $campus->id,
            'status'            => 'lost',
            'lost_reason'       => 'joined_competitor',
            'status_changed_at' => now()->startOfMonth()->addDays(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $response = $this->actingAs($director)
            ->get(route('crm.analytics.reports.lost-lead-analysis', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('reasonSummary', fn ($rows) =>
                $rows->firstWhere('lost_reason', 'no_response')?->total == 3
            )
            ->assertViewHas('reasonSummary', fn ($rows) =>
                $rows->firstWhere('lost_reason', 'joined_competitor')?->total == 2
            );
    });
});

describe('FeeCollectionReport (CRM-AR-014)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.analytics.view', 'guard_name' => 'web']);

        $directorRole   = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $counsellorRole = Role::firstOrCreate(['name' => 'counsellor',          'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);

        $directorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
        $counsellorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
    });

    it('director can access fee collection report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.fee-collection'))
            ->assertOk()
            ->assertSee('Fee Collection Report');
    });

    it('unauthenticated user is redirected', function () {
        $this->get(route('crm.analytics.reports.fee-collection'))
            ->assertRedirect();
    });

    it('user without crm.reports.view permission is forbidden', function () {
        Role::firstOrCreate(['name' => 'no_reports_role5', 'guard_name' => 'web']);
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_reports_role5');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.fee-collection'))
            ->assertForbidden();
    });

    it('date range filter is reflected in view', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.fee-collection', [
                'from' => '2026-03-01',
                'to'   => '2026-03-31',
            ]))
            ->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['from'] === '2026-03-01' && $f['to'] === '2026-03-31');
    });

    it('transactions attempted in period appear in results', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name' => 'FC Campus A',
            'code' => 'FCA',
            'is_active' => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $tx = PaymentTransaction::factory()->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'lead_uuid'      => $lead->uuid,
            'status'         => 'success',
            'amount'         => 5000.00,
            'attempted_at'   => now()->startOfMonth()->addDays(3),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.fee-collection', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('transactions', fn ($p) => $p->contains('uuid', $tx->uuid));
    });

    it('transactions outside the period are excluded', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name' => 'FC Campus B',
            'code' => 'FCB',
            'is_active' => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $old = PaymentTransaction::factory()->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'lead_uuid'      => $lead->uuid,
            'status'         => 'success',
            'attempted_at'   => now()->subMonths(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.fee-collection', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('transactions', fn ($p) => !$p->contains('uuid', $old->uuid));
    });

    it('counsellor scope returns only own-lead transactions', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name' => 'FC Campus C',
            'code' => 'FCC',
            'is_active' => true,
        ]);

        $counsellor = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $counsellor->assignRole('counsellor');

        $other = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $other->assignRole('counsellor');

        $ownLead   = Lead::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'assigned_counsellor_id' => $counsellor->id,
        ]);
        $otherLead = Lead::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'assigned_counsellor_id' => $other->id,
        ]);

        $ownTx = PaymentTransaction::factory()->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'lead_uuid'      => $ownLead->uuid,
            'attempted_at'   => now()->startOfMonth()->addDays(2),
        ]);
        $otherTx = PaymentTransaction::factory()->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'lead_uuid'      => $otherLead->uuid,
            'attempted_at'   => now()->startOfMonth()->addDays(2),
        ]);

        $this->actingAs($counsellor)
            ->get(route('crm.analytics.reports.fee-collection', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('transactions', fn ($p) =>  $p->contains('uuid', $ownTx->uuid))
            ->assertViewHas('transactions', fn ($p) => !$p->contains('uuid', $otherTx->uuid));
    });

    it('status filter narrows results to matching transactions', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name' => 'FC Campus D',
            'code' => 'FCD',
            'is_active' => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        PaymentTransaction::factory()->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'lead_uuid'      => $lead->uuid,
            'status'         => 'success',
            'attempted_at'   => now()->startOfMonth()->addDays(1),
        ]);
        PaymentTransaction::factory()->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'lead_uuid'      => $lead->uuid,
            'status'         => 'failed',
            'attempted_at'   => now()->startOfMonth()->addDays(1),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.fee-collection', [
                'from'   => now()->startOfMonth()->toDateString(),
                'to'     => now()->toDateString(),
                'status' => 'success',
            ]))
            ->assertOk()
            ->assertViewHas('transactions', fn ($p) =>
                $p->every(fn ($t) => $t->status->value === 'success')
            );
    });

    it('summary tiles reflect collected and pending amounts correctly', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name' => 'FC Campus E',
            'code' => 'FCE',
            'is_active' => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        PaymentTransaction::factory()->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'lead_uuid'      => $lead->uuid,
            'status'         => 'success',
            'amount'         => 10000.00,
            'attempted_at'   => now()->startOfMonth()->addDays(1),
        ]);
        PaymentTransaction::factory()->create([
            'institution_id' => $this->institution->id,
            'campus_id'      => $campus->id,
            'lead_uuid'      => $lead->uuid,
            'status'         => 'pending',
            'amount'         => 5000.00,
            'attempted_at'   => now()->startOfMonth()->addDays(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.fee-collection', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('summary', fn ($s) => (float) $s->collected     === 10000.00)
            ->assertViewHas('summary', fn ($s) => (float) $s->pending_amount === 5000.00);
    });
});

describe('DocumentComplianceReport (CRM-AR-015)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.analytics.view', 'guard_name' => 'web']);

        $directorRole   = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $counsellorRole = Role::firstOrCreate(['name' => 'counsellor',          'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);

        $directorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
        $counsellorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
    });

    it('director can access document compliance report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.document-compliance'))
            ->assertOk()
            ->assertSee('Document Compliance Report');
    });

    it('unauthenticated user is redirected', function () {
        $this->get(route('crm.analytics.reports.document-compliance'))
            ->assertRedirect();
    });

    it('user without crm.reports.view permission is forbidden', function () {
        Role::firstOrCreate(['name' => 'no_reports_role6', 'guard_name' => 'web']);
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_reports_role6');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.document-compliance'))
            ->assertForbidden();
    });

    it('date range filter is reflected in view', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.document-compliance', [
                'from' => '2026-02-01',
                'to'   => '2026-02-28',
            ]))
            ->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['from'] === '2026-02-01' && $f['to'] === '2026-02-28');
    });

    it('applications submitted in period appear with their document counts', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'DC Campus A',
            'code'           => 'DCA',
            'is_active'      => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $app = Application::factory()->for($this->institution)->create([
            'campus_id'    => $campus->id,
            'lead_uuid'    => $lead->uuid,
            'submitted_at' => now()->startOfMonth()->addDays(3),
        ]);

        ApplicationDocument::factory()->create([
            'institution_id'  => $this->institution->id,
            'application_uuid' => $app->uuid,
            'lead_uuid'        => $lead->uuid,
            'status'           => 'verified',
        ]);
        ApplicationDocument::factory()->create([
            'institution_id'  => $this->institution->id,
            'application_uuid' => $app->uuid,
            'lead_uuid'        => $lead->uuid,
            'status'           => 'rejected',
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.document-compliance', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('applications', fn ($p) => $p->contains('uuid', $app->uuid))
            ->assertViewHas('applications', fn ($p) =>
                (int) $p->firstWhere('uuid', $app->uuid)?->verified_docs === 1 &&
                (int) $p->firstWhere('uuid', $app->uuid)?->rejected_docs === 1
            );
    });

    it('applications outside the period are excluded', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'DC Campus B',
            'code'           => 'DCB',
            'is_active'      => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $old = Application::factory()->for($this->institution)->create([
            'campus_id'    => $campus->id,
            'lead_uuid'    => $lead->uuid,
            'submitted_at' => now()->subMonths(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.document-compliance', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('applications', fn ($p) => !$p->contains('uuid', $old->uuid));
    });

    it('counsellor scope sees only own applications', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'DC Campus C',
            'code'           => 'DCC',
            'is_active'      => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $counsellor = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $counsellor->assignRole('counsellor');

        $other = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $other->assignRole('counsellor');

        $ownApp = Application::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'lead_uuid'              => $lead->uuid,
            'assigned_counsellor_id' => $counsellor->id,
            'submitted_at'           => now()->startOfMonth()->addDays(1),
        ]);
        $otherApp = Application::factory()->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'lead_uuid'              => $lead->uuid,
            'assigned_counsellor_id' => $other->id,
            'submitted_at'           => now()->startOfMonth()->addDays(1),
        ]);

        $this->actingAs($counsellor)
            ->get(route('crm.analytics.reports.document-compliance', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('applications', fn ($p) =>  $p->contains('uuid', $ownApp->uuid))
            ->assertViewHas('applications', fn ($p) => !$p->contains('uuid', $otherApp->uuid));
    });

    it('compliance filter rejected shows only applications with rejected documents', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'DC Campus D',
            'code'           => 'DCD',
            'is_active'      => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $appWithRejection = Application::factory()->for($this->institution)->create([
            'campus_id'    => $campus->id,
            'lead_uuid'    => $lead->uuid,
            'submitted_at' => now()->startOfMonth()->addDays(2),
        ]);
        ApplicationDocument::factory()->create([
            'institution_id'   => $this->institution->id,
            'application_uuid' => $appWithRejection->uuid,
            'lead_uuid'        => $lead->uuid,
            'status'           => 'rejected',
        ]);

        $cleanApp = Application::factory()->for($this->institution)->create([
            'campus_id'    => $campus->id,
            'lead_uuid'    => $lead->uuid,
            'submitted_at' => now()->startOfMonth()->addDays(2),
        ]);
        ApplicationDocument::factory()->create([
            'institution_id'   => $this->institution->id,
            'application_uuid' => $cleanApp->uuid,
            'lead_uuid'        => $lead->uuid,
            'status'           => 'verified',
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.document-compliance', [
                'from'       => now()->startOfMonth()->toDateString(),
                'to'         => now()->toDateString(),
                'compliance' => 'rejected',
            ]))
            ->assertOk()
            ->assertViewHas('applications', fn ($p) =>  $p->contains('uuid', $appWithRejection->uuid))
            ->assertViewHas('applications', fn ($p) => !$p->contains('uuid', $cleanApp->uuid));
    });

    it('summary reflects correct document counts for the period', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'DC Campus E',
            'code'           => 'DCE',
            'is_active'      => true,
        ]);
        $lead = Lead::factory()->for($this->institution)->create(['campus_id' => $campus->id]);

        $app = Application::factory()->for($this->institution)->create([
            'campus_id'    => $campus->id,
            'lead_uuid'    => $lead->uuid,
            'submitted_at' => now()->startOfMonth()->addDays(1),
        ]);

        ApplicationDocument::factory(3)->create([
            'institution_id'   => $this->institution->id,
            'application_uuid' => $app->uuid,
            'lead_uuid'        => $lead->uuid,
            'status'           => 'verified',
        ]);
        ApplicationDocument::factory()->create([
            'institution_id'   => $this->institution->id,
            'application_uuid' => $app->uuid,
            'lead_uuid'        => $lead->uuid,
            'status'           => 'rejected',
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.document-compliance', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('summary', fn ($s) => $s->verified_docs === 3)
            ->assertViewHas('summary', fn ($s) => $s->rejected_docs === 1)
            ->assertViewHas('summary', fn ($s) => $s->total_docs    === 4);
    });
});

describe('YearOnYearReport (CRM-AR-016)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.analytics.view', 'guard_name' => 'web']);

        $directorRole   = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $counsellorRole = Role::firstOrCreate(['name' => 'counsellor',          'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);

        $directorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
        $counsellorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
    });

    it('director can access year-on-year report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.year-on-year'))
            ->assertOk()
            ->assertSee('Year-on-Year Comparison');
    });

    it('unauthenticated user is redirected', function () {
        $this->get(route('crm.analytics.reports.year-on-year'))
            ->assertRedirect();
    });

    it('user without crm.reports.view permission is forbidden', function () {
        Role::firstOrCreate(['name' => 'no_reports_role7', 'guard_name' => 'web']);
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_reports_role7');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.year-on-year'))
            ->assertForbidden();
    });

    it('year filter is reflected in view', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.year-on-year', ['year' => '2025']))
            ->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['year'] === '2025')
            ->assertViewHas('summary', fn ($s) => $s->year === 2025 && $s->prev_year === 2024);
    });

    it('current year lead count is accurate', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'YoY Campus A',
            'code'           => 'YOY1',
            'is_active'      => true,
        ]);

        // 3 leads created in current year
        Lead::factory(3)->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'created_at' => now()->startOfYear()->addDays(10),
        ]);
        // 1 lead created last year — should count in prev_leads
        Lead::factory()->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'created_at' => now()->subYear()->startOfYear()->addDays(5),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.year-on-year', ['year' => now()->year]))
            ->assertOk()
            ->assertViewHas('summary', fn ($s) => $s->leads['current'] === 3)
            ->assertViewHas('summary', fn ($s) => $s->leads['previous'] === 1);
    });

    it('enrolled count reflects leads in fee_paid or enrolled status', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'YoY Campus B',
            'code'           => 'YOY2',
            'is_active'      => true,
        ]);

        Lead::factory(2)->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'status'     => 'enrolled',
            'created_at' => now()->startOfYear()->addDays(5),
        ]);
        Lead::factory()->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'status'     => 'new_enquiry',
            'created_at' => now()->startOfYear()->addDays(5),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.year-on-year', ['year' => now()->year]))
            ->assertOk()
            ->assertViewHas('summary', fn ($s) => $s->enrolled['current'] === 2);
    });

    it('group_by=source breakdown groups leads by source', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'YoY Campus C',
            'code'           => 'YOY3',
            'is_active'      => true,
        ]);

        Lead::factory(3)->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'source'     => 'google_ads',
            'created_at' => now()->startOfYear()->addDays(2),
        ]);
        Lead::factory(2)->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'source'     => 'facebook',
            'created_at' => now()->startOfYear()->addDays(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.year-on-year', [
                'year'     => now()->year,
                'group_by' => 'source',
            ]))
            ->assertOk()
            ->assertViewHas('breakdown', fn ($rows) =>
                $rows->firstWhere('label_key', 'google_ads')?->current_leads == 3
            )
            ->assertViewHas('breakdown', fn ($rows) =>
                $rows->firstWhere('label_key', 'facebook')?->current_leads == 2
            );
    });

    it('counsellor scope returns only own-lead data', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'YoY Campus D',
            'code'           => 'YOY4',
            'is_active'      => true,
        ]);

        $counsellor = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $counsellor->assignRole('counsellor');

        $other = User::factory()->for($this->institution)->create(['campus_id' => $campus->id]);
        $other->assignRole('counsellor');

        // 2 leads assigned to counsellor in current year
        Lead::factory(2)->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'assigned_counsellor_id' => $counsellor->id,
            'created_at'             => now()->startOfYear()->addDays(3),
        ]);
        // 5 leads assigned to other counsellor — should not appear in counsellor's view
        Lead::factory(5)->for($this->institution)->create([
            'campus_id'              => $campus->id,
            'assigned_counsellor_id' => $other->id,
            'created_at'             => now()->startOfYear()->addDays(3),
        ]);

        $this->actingAs($counsellor)
            ->get(route('crm.analytics.reports.year-on-year', ['year' => now()->year]))
            ->assertOk()
            ->assertViewHas('summary', fn ($s) => $s->leads['current'] === 2);
    });

    it('delta and pct are computed correctly in summary', function () {
        $campus = Campus::create([
            'institution_id' => $this->institution->id,
            'name'           => 'YoY Campus E',
            'code'           => 'YOY5',
            'is_active'      => true,
        ]);

        Lead::factory(4)->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'created_at' => now()->startOfYear()->addDays(1),
        ]);
        Lead::factory(2)->for($this->institution)->create([
            'campus_id'  => $campus->id,
            'created_at' => now()->subYear()->startOfYear()->addDays(1),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.year-on-year', ['year' => now()->year]))
            ->assertOk()
            ->assertViewHas('summary', fn ($s) => $s->leads['delta'] === 2)
            ->assertViewHas('summary', fn ($s) => $s->leads['pct']   === 100.0);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// AR-017 — Agent Performance Report
// ─────────────────────────────────────────────────────────────────────────────

describe('AgentPerformanceReport (CRM-AR-017)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        Permission::firstOrCreate(['name' => 'crm.reports.view',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.analytics.view', 'guard_name' => 'web']);

        $directorRole   = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $managerRole    = Role::firstOrCreate(['name' => 'admissions_manager',  'guard_name' => 'web']);
        $counsellorRole = Role::firstOrCreate(['name' => 'counsellor',          'guard_name' => 'web']);

        $directorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
        $managerRole->givePermissionTo(['crm.reports.view',  'crm.analytics.view']);
        $counsellorRole->givePermissionTo(['crm.reports.view', 'crm.analytics.view']);
    });

    it('director can access agent performance report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.agent-performance'))
            ->assertOk()
            ->assertSee('Agent Performance Report');
    });

    it('manager can access agent performance report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_manager');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.agent-performance'))
            ->assertOk();
    });

    it('counsellor can access agent performance report', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('counsellor');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.agent-performance'))
            ->assertOk();
    });

    it('unauthenticated user is redirected', function () {
        $this->get(route('crm.analytics.reports.agent-performance'))
            ->assertRedirect();
    });

    it('user without crm.reports.view permission is forbidden', function () {
        $role = Role::firstOrCreate(['name' => 'no_reports_role_ap', 'guard_name' => 'web']);
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('no_reports_role_ap');

        $this->actingAs($user)
            ->get(route('crm.analytics.reports.agent-performance'))
            ->assertForbidden();
    });

    it('report row shows correct leads_referred count for agent', function () {
        $agent = Agent::create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Test Agent AR017',
            'email'           => 'ar017agent@example.com',
            'password'        => bcrypt('secret'),
            'agreement_start' => now()->subYear()->toDateString(),
            'status'          => 'active',
        ]);

        // 3 leads referred by the agent in the current month
        Lead::factory(3)->for($this->institution)->create([
            'agent_id'   => $agent->id,
            'created_at' => now()->startOfMonth()->addDays(2),
        ]);

        // 1 lead from a different institution — must not appear
        $other = Institution::factory()->create();
        Lead::factory()->for($other)->create([
            'agent_id'   => $agent->id,
            'created_at' => now()->startOfMonth()->addDays(2),
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.agent-performance', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->firstWhere('id', $agent->id)?->leads_referred == 3);
    });

    it('commission_accrued sub-select sums accruals in period', function () {
        $agent = Agent::create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Commission Agent AR017',
            'email'           => 'ar017comm@example.com',
            'password'        => bcrypt('secret'),
            'agreement_start' => now()->subYear()->toDateString(),
            'status'          => 'active',
        ]);

        $programme   = CrmProgramme::factory()->for($this->institution)->create();
        $lead        = Lead::factory()->for($this->institution)->create(['agent_id' => $agent->id]);
        $application = Application::factory()->for($this->institution)->create(['lead_uuid' => $lead->uuid]);

        $accrualBase = [
            'institution_id'       => $this->institution->id,
            'agent_id'             => $agent->id,
            'lead_id'              => $lead->id,
            'application_id'       => $application->id,
            'programme_id'         => $programme->id,
            'accrual_basis_amount' => 10000,
        ];

        AgentCommissionAccrual::create(array_merge($accrualBase, [
            'commission_amount' => 500,
            'status'            => 'approved',
            'accrued_at'        => now()->startOfMonth()->addDays(1),
        ]));
        AgentCommissionAccrual::create(array_merge($accrualBase, [
            'commission_amount' => 400,
            'status'            => 'paid',
            'accrued_at'        => now()->startOfMonth()->addDays(3),
        ]));

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.agent-performance', [
                'from' => now()->startOfMonth()->toDateString(),
                'to'   => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('rows', fn ($rows) => (float) $rows->firstWhere('id', $agent->id)?->commission_accrued == 900.0)
            ->assertViewHas('rows', fn ($rows) => (float) $rows->firstWhere('id', $agent->id)?->commission_paid    == 400.0);
    });

    it('agent_status filter excludes inactive agents', function () {
        $active = Agent::create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Active Agent',
            'email'           => 'ar017active@example.com',
            'password'        => bcrypt('secret'),
            'agreement_start' => now()->subYear()->toDateString(),
            'status'          => 'active',
        ]);
        $inactive = Agent::create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Inactive Agent',
            'email'           => 'ar017inactive@example.com',
            'password'        => bcrypt('secret'),
            'agreement_start' => now()->subYear()->toDateString(),
            'status'          => 'inactive',
        ]);

        $director = User::factory()->for($this->institution)->create();
        $director->assignRole('admissions_director');

        $this->actingAs($director)
            ->get(route('crm.analytics.reports.agent-performance', ['agent_status' => 'active']))
            ->assertOk()
            ->assertViewHas('rows', fn ($rows) =>  $rows->contains('id', $active->id))
            ->assertViewHas('rows', fn ($rows) => !$rows->contains('id', $inactive->id));
    });
});
