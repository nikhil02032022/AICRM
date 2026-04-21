<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-DM-001 to CRM-DM-010 — Document management permissions
class CrmDocumentManagementRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'document.checklist.manage',
            'document.upload',
            'document.review',
            'document.bulk_download',
            'document.completeness.view',
        ];

        $created = [];
        foreach ($permissions as $name) {
            $created[$name] = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $assignments = [
            'admin' => $permissions,
            'institution-admin' => $permissions,
            'finance' => [
                'document.completeness.view',
            ],
            'manager' => [
                'document.review',
                'document.bulk_download',
                'document.completeness.view',
            ],
            'counsellor' => [
                'document.upload',
                'document.review',
                'document.completeness.view',
            ],
        ];

        foreach ($assignments as $roleName => $perms) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            foreach ($perms as $permName) {
                if (! $role->hasPermissionTo($created[$permName])) {
                    $role->givePermissionTo($created[$permName]);
                }
            }
        }
    }
}
