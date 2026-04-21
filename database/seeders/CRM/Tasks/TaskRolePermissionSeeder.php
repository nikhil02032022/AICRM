<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\Tasks;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-TF-001 to TF-009 — Task management RBAC permissions
class TaskRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'crm.tasks.index',
            'crm.tasks.create',
            'crm.tasks.edit',
            'crm.tasks.delete',
            'crm.tasks.complete',
            'crm.tasks.bulk-assign',
            'crm.tasks.calendar',
            'crm.tasks.team.view',
            'crm.tasks.activity-feed.view',
            'crm.task-auto-rules.manage',
            'crm.task-escalation-rules.manage',
        ];

        $created = [];
        foreach ($permissions as $name) {
            $created[$name] = Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        $assignments = [
            'junior-counsellor' => [
                'crm.tasks.index',
                'crm.tasks.create',
                'crm.tasks.complete',
                'crm.tasks.calendar',
            ],
            'senior-counsellor' => [
                'crm.tasks.index',
                'crm.tasks.create',
                'crm.tasks.edit',
                'crm.tasks.delete',
                'crm.tasks.complete',
                'crm.tasks.bulk-assign',
                'crm.tasks.calendar',
            ],
            'admissions-manager' => [
                'crm.tasks.index',
                'crm.tasks.create',
                'crm.tasks.edit',
                'crm.tasks.delete',
                'crm.tasks.complete',
                'crm.tasks.bulk-assign',
                'crm.tasks.calendar',
                'crm.tasks.team.view',
                'crm.tasks.activity-feed.view',
            ],
            'admissions-director' => [
                'crm.tasks.index',
                'crm.tasks.create',
                'crm.tasks.edit',
                'crm.tasks.delete',
                'crm.tasks.complete',
                'crm.tasks.bulk-assign',
                'crm.tasks.calendar',
                'crm.tasks.team.view',
                'crm.tasks.activity-feed.view',
            ],
            'institution-admin' => $permissions,
        ];

        foreach ($assignments as $roleName => $perms) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
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
