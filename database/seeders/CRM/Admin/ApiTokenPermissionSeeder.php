<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\Admin;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-AR-021 — Seed API token management permission for admin roles
class ApiTokenPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::firstOrCreate(['name' => 'api_token.manage', 'guard_name' => 'web']);

        $superAdmin       = Role::firstOrCreate(['name' => 'super-admin',        'guard_name' => 'web']);
        $institutionAdmin = Role::firstOrCreate(['name' => 'institution-admin',   'guard_name' => 'web']);

        $superAdmin->givePermissionTo('api_token.manage');
        $institutionAdmin->givePermissionTo('api_token.manage');
    }
}
