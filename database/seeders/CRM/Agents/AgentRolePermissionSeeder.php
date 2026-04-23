<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\Agents;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-AG-001 to CRM-AG-007 — Agent module permissions and role assignments
class AgentRolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    private array $permissions = [
        // AG-001: Agent profile management
        'crm.agents.view',
        'crm.agents.create',
        'crm.agents.edit',
        'crm.agents.deactivate',
        // AG-002: Referral codes
        'crm.agents.referral.view',
        // AG-004: Commission structures
        'crm.agents.commission-structures.manage',
        // AG-005: Commission accruals (view only for CRM staff)
        'crm.agents.accruals.view',
        // AG-007: Performance report
        'crm.agents.report.view',
    ];

    public function run(): void
    {
        // Ensure all permissions exist
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // admissions_manager: full agent management
        $manager = Role::firstOrCreate(['name' => 'admissions_manager', 'guard_name' => 'web']);
        $manager->givePermissionTo($this->permissions);

        // admissions_director: full agent management + report
        $director = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $director->givePermissionTo($this->permissions);

        // institution-admin: full access
        $admin = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($this->permissions);

        // super-admin: full access
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($this->permissions);

        // senior-counsellor: view only
        $seniorCounsellor = Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);
        $seniorCounsellor->givePermissionTo([
            'crm.agents.view',
            'crm.agents.report.view',
        ]);
    }
}
