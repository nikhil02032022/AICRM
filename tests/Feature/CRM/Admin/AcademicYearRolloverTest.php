<?php

declare(strict_types=1);

// BRD: CRM-SA-003 — Academic year rollover command creates new year from active

use App\Models\CRM\Admin\AcademicYear;
use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->user        = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->user->assignRole('institution-admin');
});

it('rollover command creates new year', function (): void {
    $activeYear = AcademicYear::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'label'          => '2026-27',
        'start_date'     => '2026-06-01',
        'end_date'       => '2027-05-31',
        'is_active'      => true,
        'status'         => 'active',
    ]);

    Artisan::call('crm:rollover-academic-year', [
        'institution_id' => $this->institution->id,
        'new_year_label' => '2027-28',
    ]);

    expect(
        AcademicYear::withoutGlobalScopes()
            ->where('label', '2027-28')
            ->where('institution_id', $this->institution->id)
            ->exists()
    )->toBeTrue();
});

it('rollover preserves original year', function (): void {
    $activeYear = AcademicYear::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'label'          => '2026-27',
        'start_date'     => '2026-06-01',
        'end_date'       => '2027-05-31',
        'is_active'      => true,
        'status'         => 'active',
    ]);

    Artisan::call('crm:rollover-academic-year', [
        'institution_id' => $this->institution->id,
        'new_year_label' => '2027-28',
    ]);

    expect(
        AcademicYear::withoutGlobalScopes()
            ->where('label', '2026-27')
            ->where('institution_id', $this->institution->id)
            ->exists()
    )->toBeTrue();
});

it('rollover command exits with failure when no active year exists', function (): void {
    $exitCode = Artisan::call('crm:rollover-academic-year', [
        'institution_id' => $this->institution->id,
        'new_year_label' => '2027-28',
    ]);

    expect($exitCode)->toBe(1);

    expect(
        AcademicYear::withoutGlobalScopes()
            ->where('label', '2027-28')
            ->exists()
    )->toBeFalse();
});

it('rolled over year has rolled_over_from_id pointing to original', function (): void {
    $activeYear = AcademicYear::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'label'          => '2026-27',
        'start_date'     => '2026-06-01',
        'end_date'       => '2027-05-31',
        'is_active'      => true,
        'status'         => 'active',
    ]);

    Artisan::call('crm:rollover-academic-year', [
        'institution_id' => $this->institution->id,
        'new_year_label' => '2027-28',
    ]);

    $newYear = AcademicYear::withoutGlobalScopes()
        ->where('label', '2027-28')
        ->first();

    expect($newYear->rolled_over_from_id)->toBe($activeYear->id);
});
