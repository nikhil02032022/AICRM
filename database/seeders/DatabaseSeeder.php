<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,  // must run before RoleSeeder
            RoleSeeder::class,
            UserSeeder::class,
            QuestionnaireSeeder::class,
            CrmFeePaymentRolePermissionSeeder::class,
            CrmScholarshipRolePermissionSeeder::class,
            CrmDocumentManagementRolePermissionSeeder::class,
            CrmFeeInstallmentRolePermissionSeeder::class,
        ]);
    }
}
