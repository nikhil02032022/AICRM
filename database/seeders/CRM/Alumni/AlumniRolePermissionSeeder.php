<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\Alumni;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AlumniRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'crm.alumni.pipeline.view',
            'crm.alumni.pipeline.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $institutionAdmin = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
        $admissionsDirector = Role::firstOrCreate(['name' => 'admissions-director', 'guard_name' => 'web']);

        $superAdmin->givePermissionTo($permissions);
        $institutionAdmin->givePermissionTo($permissions);

        // admissions-director gets view only
        $admissionsDirector->givePermissionTo([
            'crm.alumni.pipeline.view',
        ]);
    }
}
