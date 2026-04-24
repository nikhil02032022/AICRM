<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\AI;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// BRD: CRM-AI-001 — Seed permissions for AI conversion probability prediction feature
class AiPredictionPermissionSeeder extends Seeder
{
    /** @var list<string> */
    private array $permissions = [
        'ai.prediction.view',     // View conversion probability badge and prediction data
        'ai.prediction.feedback', // Accept or reject a conversion probability prediction
    ];

    public function run(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // super-admin: full access
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($this->permissions);

        // institution-admin: full access
        $admin = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($this->permissions);

        // admissions_director: full access
        $director = Role::firstOrCreate(['name' => 'admissions_director', 'guard_name' => 'web']);
        $director->givePermissionTo($this->permissions);

        // admissions_manager: full access
        $manager = Role::firstOrCreate(['name' => 'admissions_manager', 'guard_name' => 'web']);
        $manager->givePermissionTo($this->permissions);

        // senior-counsellor: full access (accept/reject is their primary interaction)
        $seniorCounsellor = Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);
        $seniorCounsellor->givePermissionTo($this->permissions);

        // counsellor: view + feedback
        $counsellor = Role::firstOrCreate(['name' => 'counsellor', 'guard_name' => 'web']);
        $counsellor->givePermissionTo($this->permissions);
    }
}
