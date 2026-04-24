<?php

declare(strict_types=1);

// BRD: CRM-CR-001 — Explicit consent captured at point of lead creation
// BRD: CRM-CR-002 — Stored with timestamp, IP address, form version

use App\Models\CRM\ConsentRecord;
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

    $this->institution = Institution::factory()->create();

    // Grant leads.create permission to counsellor role
    Permission::firstOrCreate(['name' => 'crm.leads.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'crm.leads.view', 'guard_name' => 'web']);
    $counsellorRole = Role::firstOrCreate(['name' => 'counsellor', 'guard_name' => 'web']);
    $counsellorRole->givePermissionTo(['crm.leads.create', 'crm.leads.view']);

    $this->counsellor = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->counsellor->assignRole('counsellor');
});

it('lead creation with consent_given=true creates ConsentRecord', function (): void {
    $response = $this->actingAs($this->counsellor)
        ->postJson(route('crm.leads.store'), [
            'first_name'          => 'Rahul',
            'last_name'           => 'Mehta',
            'email'               => 'rahul.mehta@example.com',
            'mobile'              => '9876543210',
            'source'              => 'walk_in',
            'consent_given'       => 1,
            'consent_form_version' => '1.0',
        ]);

    $response->assertStatus(201);

    $lead = Lead::withoutGlobalScopes()
        ->where('institution_id', $this->institution->id)
        ->latest()
        ->first();

    expect($lead)->not->toBeNull();

    expect(
        ConsentRecord::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->exists()
    )->toBeTrue();
});

it('ConsentRecord has consent_ip and consent_form_version populated', function (): void {
    $response = $this->actingAs($this->counsellor)
        ->postJson(route('crm.leads.store'), [
            'first_name'          => 'Anita',
            'last_name'           => 'Patel',
            'email'               => 'anita.patel@example.com',
            'mobile'              => '9123456789',
            'source'              => 'walk_in',
            'consent_given'       => 1,
            'consent_form_version' => '1.0',
        ]);

    $response->assertStatus(201);

    $lead = Lead::withoutGlobalScopes()
        ->where('institution_id', $this->institution->id)
        ->latest()
        ->first();

    $consentRecord = ConsentRecord::withoutGlobalScopes()
        ->where('lead_id', $lead->id)
        ->latest()
        ->first();

    expect($consentRecord)->not->toBeNull();
    // consent_ip is stored in consent_ip column per the consent_records table schema
    expect($consentRecord->consent_ip)->not->toBeNull();
    // consent_form_version is stored in consent_form_version column
    expect($consentRecord->consent_form_version)->toBe('1.0');
});

it('lead creation without consent does not create ConsentRecord', function (): void {
    $response = $this->actingAs($this->counsellor)
        ->postJson(route('crm.leads.store'), [
            'first_name'    => 'No',
            'last_name'     => 'Consent',
            'email'         => 'no.consent@example.com',
            'mobile'        => '9999000001',
            'source'        => 'walk_in',
            'consent_given' => 0,
        ]);

    // Request may be rejected by validation depending on StoreLeadRequest rules
    // If it succeeds (consent_given = 0 is allowed), verify no ConsentRecord created
    if ($response->status() === 201) {
        $lead = Lead::withoutGlobalScopes()
            ->where('institution_id', $this->institution->id)
            ->latest()
            ->first();

        expect(
            ConsentRecord::withoutGlobalScopes()
                ->where('lead_id', $lead->id)
                ->exists()
        )->toBeFalse();
    } else {
        // Validation rejected the request — that is also acceptable behaviour
        $response->assertUnprocessable();
    }
});
