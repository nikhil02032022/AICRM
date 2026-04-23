<?php

declare(strict_types=1);

// BRD: CRM-AG-004 — Agent commission structure CRUD feature tests

use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionStructure;
use App\Models\CRM\Agents\AgentReferralCode;
use App\Models\CRM\Institution;
use App\Models\CRM\CrmProgramme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('AgentCommissionStructure CRUD (CRM-AG-004)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->programme   = CrmProgramme::factory()->create(['institution_id' => $this->institution->id]);

        $this->manager = User::factory()->create(['institution_id' => $this->institution->id]);
        $role = Role::firstOrCreate(['name' => 'admissions_manager', 'guard_name' => 'web']);
        foreach (['crm.agents.view', 'crm.agents.edit'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role->givePermissionTo(['crm.agents.view', 'crm.agents.edit']);
        $this->manager->assignRole($role);

        $this->agent = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Struct Agent',
            'email'           => 'struct@agent.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);
        AgentReferralCode::withoutGlobalScopes()->create([
            'agent_id' => $this->agent->id, 'institution_id' => $this->institution->id,
            'code' => 'STRUCT-AG-0001', 'url_slug' => 'struct-ag-0001',
        ]);
    });

    it('can create a PerEnrolment commission structure', function () {
        $response = $this->actingAs($this->manager)
            ->post("/crm/agents/{$this->agent->id}/commission-structures", [
                'programme_id'   => $this->programme->id,
                'structure_type' => 'per_enrolment',
                'amount'         => 5000,
                'effective_from' => now()->toDateString(),
            ]);

        $response->assertRedirect();

        expect(AgentCommissionStructure::withoutGlobalScopes()->count())->toBe(1);
        $structure = AgentCommissionStructure::withoutGlobalScopes()->first();
        expect((float)$structure->amount)->toBe(5000.0);
    });

    it('validates that amount is required for PerEnrolment type', function () {
        $response = $this->actingAs($this->manager)
            ->post("/crm/agents/{$this->agent->id}/commission-structures", [
                'programme_id'   => $this->programme->id,
                'structure_type' => 'per_enrolment',
                'amount'         => null,
                'effective_from' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('amount');
    });

    it('can create a PercentageFee commission structure', function () {
        $response = $this->actingAs($this->manager)
            ->post("/crm/agents/{$this->agent->id}/commission-structures", [
                'programme_id'   => $this->programme->id,
                'structure_type' => 'percentage_fee',
                'percentage'     => 8.5,
                'effective_from' => now()->toDateString(),
            ]);

        $response->assertRedirect();

        $structure = AgentCommissionStructure::withoutGlobalScopes()->first();
        expect((float)$structure->percentage)->toBe(8.5);
    });
});
