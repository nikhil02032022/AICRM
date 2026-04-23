<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\Analytics;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-AR-001 to CRM-AR-021 — Analytics and reporting permissions per role
class AnalyticsRolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    private array $permissions = [
        // AR-001 to AR-006: Dashboard access
        'crm.analytics.view',           // all authenticated CRM users
        'crm.analytics.institution',    // institution-wide dashboard (manager, director)
        'crm.analytics.marketing',      // marketing campaign dashboard (manager, director)
        'crm.analytics.executive',      // executive KPI dashboard (director only)

        // AR-009 to AR-017: Standard reports
        'crm.reports.view',             // view and run reports

        // AR-018, AR-020: Custom reports and scheduling
        'crm.reports.manage',           // create, edit, delete, schedule reports
        'crm.reports.export',           // export reports to Excel / PDF
    ];

    public function run(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Counsellor: view own analytics and run standard reports
        $counsellor = Role::firstOrCreate(['name' => 'counsellor', 'guard_name' => 'web']);
        $counsellor->givePermissionTo([
            'crm.analytics.view',
            'crm.reports.view',
        ]);

        // Senior counsellor: same as counsellor
        $seniorCounsellor = Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);
        $seniorCounsellor->givePermissionTo([
            'crm.analytics.view',
            'crm.reports.view',
        ]);

        // Admissions manager: institution + marketing dashboard + report management
        $manager = Role::firstOrCreate(['name' => 'admissions_manager', 'guard_name' => 'web']);
        $manager->givePermissionTo([
            'crm.analytics.view',
            'crm.analytics.institution',
            'crm.analytics.marketing',
            'crm.reports.view',
            'crm.reports.manage',
            'crm.reports.export',
        ]);

        // Admissions director: full analytics access including executive dashboard
        $director = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $director->givePermissionTo($this->permissions);

        // Institution admin: full access
        $admin = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($this->permissions);

        // Super admin: full access
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($this->permissions);
    }
}
