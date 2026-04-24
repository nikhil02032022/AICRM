<?php

declare(strict_types=1);

namespace Database\Seeders\CRM\Admin;

use App\Models\CRM\Institution;
use App\Models\CRM\Campus;
use App\Models\CRM\Admin\AcademicYear;
use App\Enums\CRM\Admin\AcademicYearStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        if (!App::isLocal()) {
            return;
        }

        $institution = Institution::firstOrCreate(
            ['name' => 'Demo University'],
            [
                'email' => 'admin@demouniversity.edu',
                'city' => 'Mumbai',
                'country' => 'India',
                'timezone' => 'Asia/Kolkata',
                'locale' => 'en_IN',
            ]
        );

        $campus = Campus::firstOrCreate(
            ['code' => 'MAIN', 'institution_id' => $institution->id],
            [
                'name' => 'Main Campus',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'is_active' => true,
            ]
        );

        AcademicYear::firstOrCreate(
            ['label' => '2025-26', 'institution_id' => $institution->id],
            [
                'start_date' => '2025-06-01',
                'end_date' => '2026-05-31',
                'is_active' => true,
                'status' => AcademicYearStatus::Active,
            ]
        );
    }
}
