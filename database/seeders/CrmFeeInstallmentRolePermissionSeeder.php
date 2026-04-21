<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-FM-009 — Installment plan permissions
class CrmFeeInstallmentRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'installment.plan.manage',
            'installment.apply',
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
            'finance' => $permissions,
            'manager' => ['installment.apply'],
            'counsellor' => ['installment.apply'],
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
