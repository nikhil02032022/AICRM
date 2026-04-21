<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-FM-001 to CRM-FM-013 — Permissions for fee/payment workflows
class CrmFeePaymentRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'fee_structure.manage',
            'payments.view',
            'payments.collect',
            'payments.link.share',
            'payments.refund.request',
            'payments.refund.approve',
            'fee_dashboard.view',
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
                'fee_structure.manage',
                'payments.view',
                'payments.refund.approve',
                'fee_dashboard.view',
            ],
            'manager' => [
                'payments.view',
                'payments.refund.approve',
                'fee_dashboard.view',
            ],
            'counsellor' => [
                'payments.view',
                'payments.collect',
                'payments.link.share',
                'payments.refund.request',
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
