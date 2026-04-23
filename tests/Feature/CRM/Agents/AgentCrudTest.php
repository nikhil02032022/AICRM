<?php

declare(strict_types=1);

// BRD: CRM-AG-001 — Agent CRUD and institution isolation feature tests

use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Agent CRUD (CRM-AG-001)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->other       = Institution::factory()->create();

        $this->manager = User::factory()->create(['institution_id' => $this->institution->id]);
        $role = Role::firstOrCreate(['name' => 'admissions_manager', 'guard_name' => 'web']);
        foreach (['crm.agents.view', 'crm.agents.create', 'crm.agents.edit', 'crm.agents.deactivate'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role->givePermissionTo(['crm.agents.view', 'crm.agents.create', 'crm.agents.edit', 'crm.agents.deactivate']);
        $this->manager->assignRole($role);
    });

    it('admissions manager can view agents list', function () {
        $response = $this->actingAs($this->manager)->get('/crm/agents');

        $response->assertOk();
    });

    it('creates an agent and auto-generates referral code', function () {
        $response = $this->actingAs($this->manager)->post('/crm/agents', [
            'name'             => 'New Agent',
            'email'            => 'newagent@example.com',
            'password'         => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'agreement_start'  => now()->toDateString(),
            'status'           => 'active',
        ]);

        $response->assertRedirect('/crm/agents');

        $agent = Agent::withoutGlobalScopes()
            ->where('email', 'newagent@example.com')
            ->first();

        expect($agent)->not->toBeNull();
        expect($agent->referralCode)->not->toBeNull();
    });

    it('cannot see agents from another institution', function () {
        Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->other->id,
            'name'            => 'Other Inst Agent',
            'email'           => 'other@example.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);

        $response = $this->actingAs($this->manager)->get('/crm/agents');

        $response->assertDontSee('Other Inst Agent');
    });

    it('deactivates an agent via destroy endpoint', function () {
        $agent = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Active Agent',
            'email'           => 'active@example.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);

        $this->actingAs($this->manager)->delete("/crm/agents/{$agent->id}");

        $agent->refresh();
        expect($agent->status->value)->toBe('inactive');
    });
});
