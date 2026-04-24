<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\Compliance;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ComplianceRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'crm.compliance.access',
            'crm.compliance.consent.view',
            'crm.compliance.opt-out.view',
            'crm.compliance.opt-out.process',
            'crm.compliance.data-access.view',
            'crm.compliance.data-access.process',
            'crm.compliance.erasure.view',
            'crm.compliance.erasure.schedule',
            'crm.compliance.incidents.view',
            'crm.compliance.incidents.create',
            'crm.compliance.incidents.update',
            'crm.compliance.dpa.download',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $institutionAdmin = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
        $admissionsDirector = Role::firstOrCreate(['name' => 'admissions-director', 'guard_name' => 'web']);

        $superAdmin->givePermissionTo($permissions);
        $institutionAdmin->givePermissionTo($permissions);

        // admissions-director gets view-only subset
        $admissionsDirector->givePermissionTo([
            'crm.compliance.access',
            'crm.compliance.consent.view',
            'crm.compliance.opt-out.view',
            'crm.compliance.data-access.view',
            'crm.compliance.erasure.view',
            'crm.compliance.incidents.view',
            'crm.compliance.dpa.download',
        ]);
    }
}
