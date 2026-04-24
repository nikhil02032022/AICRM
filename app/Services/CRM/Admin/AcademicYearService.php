<?php

declare(strict_types=1);

namespace App\Services\CRM\Admin;

use App\Enums\CRM\Admin\AcademicYearStatus;
use App\Models\CRM\Admin\AcademicYear;
use Illuminate\Support\Facades\DB;

// BRD: CRM-SA-003 — Academic year / admission cycle management with rollover
class AcademicYearService
{
    public function create(array $data): AcademicYear
    {
        return AcademicYear::create($data);
    }

    public function activate(AcademicYear $year): void
    {
        DB::transaction(function () use ($year) {
            AcademicYear::withoutGlobalScopes()
                ->where('institution_id', $year->institution_id)
                ->where('id', '!=', $year->id)
                ->update(['is_active' => false, 'status' => AcademicYearStatus::Closed->value]);

            $year->update(['is_active' => true, 'status' => AcademicYearStatus::Active->value]);
        });
    }

    public function rollover(AcademicYear $from, string $newLabel): AcademicYear
    {
        return DB::transaction(function () use ($from, $newLabel) {
            $from->update(['status' => AcademicYearStatus::Archived->value, 'is_active' => false]);

            $newYear = AcademicYear::create([
                'institution_id'    => $from->institution_id,
                'label'             => $newLabel,
                'start_date'        => $from->end_date->addDay(),
                'end_date'          => $from->end_date->addYear(),
                'is_active'         => true,
                'status'            => AcademicYearStatus::Active->value,
                'rolled_over_from_id' => $from->id,
            ]);

            return $newYear;
        });
    }

    public function getActive(int $institutionId): ?AcademicYear
    {
        return AcademicYear::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->first();
    }

    public function update(AcademicYear $year, array $data): AcademicYear
    {
        $year->update($data);

        return $year->refresh();
    }
}
