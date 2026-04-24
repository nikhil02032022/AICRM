<?php

declare(strict_types=1);

// BRD: NFR-MT-001 — InstitutionScope enforces strict multi-tenant isolation
// BRD: CRM-SA-001 — Institution tenancy prevents cross-tenant data access

use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institutionA = Institution::factory()->create(['name' => 'Institution Alpha']);
    $this->institutionB = Institution::factory()->create(['name' => 'Institution Beta']);

    // Ensure the institution-admin role has crm.leads.view permission
    Permission::firstOrCreate(['name' => 'crm.leads.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
    $role->givePermissionTo('crm.leads.view');

    $this->userA = User::factory()->create(['institution_id' => $this->institutionA->id]);
    $this->userA->assignRole('institution-admin');

    $this->userB = User::factory()->create(['institution_id' => $this->institutionB->id]);
    $this->userB->assignRole('institution-admin');
});

it('institution A cannot see institution B leads', function (): void {
    $leadA = Lead::withoutGlobalScopes()->create([
        'institution_id'   => $this->institutionA->id,
        'first_name'       => 'AlphaStudent',
        'last_name'        => 'Unique',
        'email'            => encrypt('alpha@example.com'),
        'mobile'           => encrypt('9000000001'),
        'source'           => 'walk_in',
        'status'           => 'new_enquiry',
        'consent_given'    => true,
        'consent_timestamp' => now(),
    ]);

    $response = $this->actingAs($this->userB)->get(route('crm.leads.index'));

    $response->assertOk();
    $response->assertDontSee('AlphaStudent');
});

it('InstitutionScope prevents cross-tenant results', function (): void {
    Lead::withoutGlobalScopes()->create([
        'institution_id'   => $this->institutionA->id,
        'first_name'       => 'AlphaOnly',
        'last_name'        => 'Lead',
        'email'            => encrypt('alphaonly@example.com'),
        'mobile'           => encrypt('9000000002'),
        'source'           => 'walk_in',
        'status'           => 'new_enquiry',
        'consent_given'    => true,
        'consent_timestamp' => now(),
    ]);

    $this->actingAs($this->userB);

    // With InstitutionScope enforced, Lead::all() scoped to userB's institution should be empty
    $leads = Lead::all();

    expect($leads)->toHaveCount(0);
});

it('institution B user can see its own leads but not institution A leads', function (): void {
    Lead::withoutGlobalScopes()->create([
        'institution_id'   => $this->institutionA->id,
        'first_name'       => 'AlphaStudent',
        'last_name'        => 'Test',
        'email'            => encrypt('alpha2@example.com'),
        'mobile'           => encrypt('9000000003'),
        'source'           => 'walk_in',
        'status'           => 'new_enquiry',
        'consent_given'    => true,
        'consent_timestamp' => now(),
    ]);

    Lead::withoutGlobalScopes()->create([
        'institution_id'   => $this->institutionB->id,
        'first_name'       => 'BetaStudent',
        'last_name'        => 'Test',
        'email'            => encrypt('beta@example.com'),
        'mobile'           => encrypt('9000000004'),
        'source'           => 'walk_in',
        'status'           => 'new_enquiry',
        'consent_given'    => true,
        'consent_timestamp' => now(),
    ]);

    $this->actingAs($this->userB);

    $leads = Lead::all();

    expect($leads)->toHaveCount(1);
    expect($leads->first()->first_name)->toBe('BetaStudent');
});
