<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-FM-006 to CRM-FM-008 — Scholarship/waiver permissions
class CrmScholarshipRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'scholarship.category.manage',
            'scholarship.award.submit',
            'scholarship.award.approve.manager',
            'scholarship.award.approve.finance',
            'scholarship.impact.view',
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
                'scholarship.category.manage',
                'scholarship.award.approve.finance',
                'scholarship.impact.view',
            ],
            'manager' => [
                'scholarship.award.approve.manager',
                'scholarship.impact.view',
            ],
            'counsellor' => [
                'scholarship.award.submit',
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
