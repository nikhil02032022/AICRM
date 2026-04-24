<?php

declare(strict_types=1);

namespace App\Console\Commands\CRM\Admin;

use App\Models\CRM\Admin\AcademicYear;
use App\Services\CRM\Admin\AcademicYearService;
use Illuminate\Console\Command;

// BRD: CRM-SA-003 — Academic year rollover command
class RolloverAcademicYear extends Command
{
    protected $signature = 'crm:rollover-academic-year
                            {institution_id : The institution ID to rollover}
                            {new_year_label : Label for the new academic year (e.g. 2026-27)}';

    protected $description = 'Rollover the active academic year for an institution and create a new one';

    public function handle(AcademicYearService $service): int
    {
        $institutionId = (int) $this->argument('institution_id');
        $newLabel      = $this->argument('new_year_label');

        $active = $service->getActive($institutionId);

        if (! $active) {
            $this->error("No active academic year found for institution ID {$institutionId}.");

            return self::FAILURE;
        }

        $this->info("Rolling over academic year [{$active->label}] → [{$newLabel}] for institution #{$institutionId}...");

        $newYear = $service->rollover($active, $newLabel);

        $this->info("Done. New academic year created: #{$newYear->id} [{$newYear->label}]");
        $this->line("  Start: {$newYear->start_date->format('d M Y')}");
        $this->line("  End:   {$newYear->end_date->format('d M Y')}");
        $this->line("  Rolled over from: #{$active->id} [{$active->label}]");

        return self::SUCCESS;
    }
}
