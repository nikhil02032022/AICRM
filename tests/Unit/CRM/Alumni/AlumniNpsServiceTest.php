<?php

declare(strict_types=1);

// BRD: CRM-AL-004 — Unit tests for AlumniNpsService

use App\Enums\CRM\Alumni\NpsSnapshotSource;
use App\Models\CRM\Admin\AcademicYear;
use App\Models\CRM\Alumni\AlumniNpsSnapshot;
use App\Models\CRM\Institution;
use App\Services\CRM\Alumni\AlumniNpsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();
    $this->service     = new AlumniNpsService();

    $this->academicYear = AcademicYear::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'label'          => '2025-2026',
        'start_date'     => '2025-06-01',
        'end_date'       => '2026-05-31',
        'is_active'      => true,
        'status'         => 'active',
    ]);
});

it('storeSnapshot() persists a record and computes nps_score as promoters_pct minus detractors_pct', function (): void {
    $snapshot = $this->service->storeSnapshot([
        'institution_id'   => $this->institution->id,
        'academic_year_id' => $this->academicYear->id,
        'programme_id'     => null,
        'promoters_pct'    => 65.0,
        'neutrals_pct'     => 20.0,
        'detractors_pct'   => 15.0,
        'survey_date'      => '2026-03-01',
        'source'           => NpsSnapshotSource::Manual->value,
    ]);

    expect($snapshot->nps_score)->toBe(50);
    expect($snapshot->promoters_pct)->toEqual('65.00');
    expect($snapshot->detractors_pct)->toEqual('15.00');
    expect($snapshot->source->value)->toBe(NpsSnapshotSource::Manual->value);
    expect(AlumniNpsSnapshot::withoutGlobalScopes()->where('id', $snapshot->id)->exists())->toBeTrue();
});

it('storeSnapshot() throws ValidationException when percentages do not sum to 100', function (): void {
    expect(fn () => $this->service->storeSnapshot([
        'institution_id'   => $this->institution->id,
        'academic_year_id' => $this->academicYear->id,
        'promoters_pct'    => 60.0,
        'neutrals_pct'     => 20.0,
        'detractors_pct'   => 15.0, // sum = 95, not 100
        'survey_date'      => '2026-03-01',
        'source'           => NpsSnapshotSource::Manual->value,
    ]))->toThrow(ValidationException::class);
});

it('getLatestScore() returns the most recent snapshot by survey_date', function (): void {
    AlumniNpsSnapshot::withoutGlobalScopes()->create([
        'institution_id'   => $this->institution->id,
        'academic_year_id' => $this->academicYear->id,
        'nps_score'        => 30,
        'promoters_pct'    => 50,
        'neutrals_pct'     => 30,
        'detractors_pct'   => 20,
        'survey_date'      => '2026-01-01',
        'source'           => NpsSnapshotSource::Manual->value,
    ]);

    AlumniNpsSnapshot::withoutGlobalScopes()->create([
        'institution_id'   => $this->institution->id,
        'academic_year_id' => $this->academicYear->id,
        'nps_score'        => 55,
        'promoters_pct'    => 70,
        'neutrals_pct'     => 15,
        'detractors_pct'   => 15,
        'survey_date'      => '2026-03-01',
        'source'           => NpsSnapshotSource::Manual->value,
    ]);

    $latest = $this->service->getLatestScore($this->institution->id);

    expect($latest)->not->toBeNull();
    expect($latest->nps_score)->toBe(55);
    expect($latest->survey_date->toDateString())->toBe('2026-03-01');
});

it('getTrend() returns snapshots ordered by survey_date within the rolling 12-month window', function (): void {
    $dates = ['2026-01-01', '2026-02-01', '2026-03-01'];

    foreach ($dates as $i => $date) {
        AlumniNpsSnapshot::withoutGlobalScopes()->create([
            'institution_id'   => $this->institution->id,
            'academic_year_id' => $this->academicYear->id,
            'nps_score'        => 30 + $i * 10,
            'promoters_pct'    => 50,
            'neutrals_pct'     => 30,
            'detractors_pct'   => 20,
            'survey_date'      => $date,
            'source'           => NpsSnapshotSource::Manual->value,
        ]);
    }

    // One old snapshot outside the 12-month window
    AlumniNpsSnapshot::withoutGlobalScopes()->create([
        'institution_id'   => $this->institution->id,
        'academic_year_id' => $this->academicYear->id,
        'nps_score'        => 10,
        'promoters_pct'    => 40,
        'neutrals_pct'     => 30,
        'detractors_pct'   => 30,
        'survey_date'      => now()->subMonths(14)->toDateString(),
        'source'           => NpsSnapshotSource::Manual->value,
    ]);

    $trend = $this->service->getTrend($this->institution->id, 12);

    expect($trend)->toHaveCount(3);
    expect($trend->first()->survey_date->toDateString())->toBe('2026-01-01');
    expect($trend->last()->survey_date->toDateString())->toBe('2026-03-01');
});
