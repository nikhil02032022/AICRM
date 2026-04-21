<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CrmAnalyticsRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions if not exist
        $analyticsPermission = Permission::firstOrCreate([
            'name' => 'crm.analytics.view',
            'guard_name' => 'web',
        ]);
        $reportsPermission = Permission::firstOrCreate([
            'name' => 'crm.reports.view',
            'guard_name' => 'web',
        ]);

        // Assign to admin, counsellor, and institution-admin roles
        foreach (['admin', 'counsellor', 'institution-admin'] as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            if (!$role->hasPermissionTo($analyticsPermission)) {
                $role->givePermissionTo($analyticsPermission);
            }
            if (!$role->hasPermissionTo($reportsPermission)) {
                $role->givePermissionTo($reportsPermission);
            }
        }
    }
}
