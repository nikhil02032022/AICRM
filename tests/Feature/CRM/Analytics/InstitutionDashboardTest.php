<?php

declare(strict_types=1);

// BRD: CRM-AR-001 — Institution admissions dashboard feature tests

use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('InstitutionDashboard (CRM-AR-001)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();

        // Ensure analytics permissions and roles exist
        Permission::firstOrCreate(['name' => 'crm.analytics.institution', 'guard_name' => 'web']);
        $directorRole = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $directorRole->givePermissionTo('crm.analytics.institution');

        Permission::firstOrCreate(['name' => 'crm.analytics.view', 'guard_name' => 'web']);
        $counsellorRole = Role::firstOrCreate(['name' => 'counsellor', 'guard_name' => 'web']);
        $counsellorRole->givePermissionTo('crm.analytics.view');
    });

    it('director can access institution dashboard', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)->get(route('crm.analytics.dashboards.institution'));

        $response->assertOk()
            ->assertSee('Institution Dashboard')
            ->assertSee('Total Leads');
    });

    it('counsellor is forbidden from institution dashboard', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('counsellor');

        $response = $this->actingAs($user)->get(route('crm.analytics.dashboards.institution'));

        $response->assertForbidden();
    });

    it('unauthenticated user is redirected', function () {
        $response = $this->get(route('crm.analytics.dashboards.institution'));

        $response->assertRedirect();
    });

    it('date range filter is passed through to view', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $response = $this->actingAs($user)->get(
            route('crm.analytics.dashboards.institution', ['from' => '2026-01-01', 'to' => '2026-01-31'])
        );

        $response->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['from'] === '2026-01-01' && $f['to'] === '2026-01-31');
    });
});
