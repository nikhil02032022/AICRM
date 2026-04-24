<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder;
use Database\Seeders\CRM\Admin\InstitutionSeeder;
use Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder;
use Database\Seeders\CRM\Analytics\AnalyticsRolePermissionSeeder;
use Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder;
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
            PortalDataSeeder::class,
            AnalyticsRolePermissionSeeder::class, // BRD: CRM-AR-007 — Analytics permissions
            SystemAdminRolePermissionSeeder::class,
            ComplianceRolePermissionSeeder::class,
            AlumniRolePermissionSeeder::class,
            InstitutionSeeder::class,
        ]);
    }
}
