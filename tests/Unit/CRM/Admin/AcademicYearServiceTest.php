<?php

declare(strict_types=1);

// BRD: CRM-SA-003 — Academic year / admission cycle management with rollover

use App\Models\CRM\Admin\AcademicYear;
use App\Models\CRM\Institution;
use App\Models\User;
use App\Services\CRM\Admin\AcademicYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->user        = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->service     = app(AcademicYearService::class);
});

it('create() creates year and returns model', function (): void {
    $year = $this->service->create([
        'institution_id' => $this->institution->id,
        'label'          => '2026-27',
        'start_date'     => '2026-06-01',
        'end_date'       => '2027-05-31',
        'is_active'      => false,
        'status'         => 'draft',
    ]);

    expect($year)->toBeInstanceOf(AcademicYear::class);
    expect($year->label)->toBe('2026-27');
    expect($year->exists)->toBeTrue();

    $this->assertDatabaseHas('academic_years', [
        'institution_id' => $this->institution->id,
        'label'          => '2026-27',
    ]);
});

it('activate() deactivates other years in same institution', function (): void {
    $firstYear = AcademicYear::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'label'          => '2025-26',
        'start_date'     => '2025-06-01',
        'end_date'       => '2026-05-31',
        'is_active'      => false,
        'status'         => 'draft',
    ]);

    $secondYear = AcademicYear::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'label'          => '2026-27',
        'start_date'     => '2026-06-01',
        'end_date'       => '2027-05-31',
        'is_active'      => false,
        'status'         => 'draft',
    ]);

    $this->service->activate($firstYear);

    expect($firstYear->fresh()->is_active)->toBeTrue();
    expect($secondYear->fresh()->is_active)->toBeFalse();
});

it('rollover() creates new year with rolled_over_from_id set', function (): void {
    $fromYear = AcademicYear::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'label'          => '2025-26',
        'start_date'     => '2025-06-01',
        'end_date'       => '2026-05-31',
        'is_active'      => true,
        'status'         => 'active',
    ]);

    $newYear = $this->service->rollover($fromYear, '2027-28');

    expect($newYear->exists)->toBeTrue();
    expect($newYear->label)->toBe('2027-28');
    expect($newYear->rolled_over_from_id)->toBe($fromYear->id);

    $this->assertDatabaseHas('academic_years', [
        'label'              => '2027-28',
        'rolled_over_from_id' => $fromYear->id,
        'institution_id'     => $this->institution->id,
    ]);
});
