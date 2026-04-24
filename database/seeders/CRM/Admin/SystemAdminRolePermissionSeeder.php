<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\Admin;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemAdminRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'crm.admin.access',
            'crm.admin.institutions.view',
            'crm.admin.institutions.edit',
            'crm.admin.campuses.manage',
            'crm.admin.academic-years.manage',
            'crm.admin.audit-logs.view',
            'crm.admin.data-import.manage',
            'crm.admin.data-export.manage',
            'crm.admin.system-config.manage',
            'crm.admin.custom-fields.manage',
            'crm.admin.notification-templates.manage',
            'crm.admin.backups.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $institutionAdmin = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
        $admissionsDirector = Role::firstOrCreate(['name' => 'admissions-director', 'guard_name' => 'web']);

        $superAdmin->givePermissionTo($permissions);
        $institutionAdmin->givePermissionTo($permissions);

        // admissions-director gets read-only subset
        $admissionsDirector->givePermissionTo([
            'crm.admin.access',
            'crm.admin.audit-logs.view',
            'crm.admin.campuses.manage',
            'crm.admin.academic-years.manage',
        ]);
    }
}
