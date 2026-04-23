<?php

declare(strict_types=1);

namespace App\Console\Commands\CRM\Analytics;

use App\Jobs\CRM\Analytics\RefreshDashboardSnapshotJob;
use App\Models\CRM\Institution;
use Illuminate\Console\Command;

// BRD: CRM-AR-001, AR-006 — Nightly command to dispatch RefreshDashboardSnapshotJob for every active institution
class RefreshDashboardSnapshotsCommand extends Command
{
    protected $signature = 'crm:analytics:refresh-snapshots';

    protected $description = 'Dispatch dashboard metric snapshot refresh jobs for all active institutions.';

    public function handle(): int
    {
        $institutions = Institution::withoutGlobalScopes()
            ->where('is_active', true)
            ->pluck('id');

        foreach ($institutions as $institutionId) {
            RefreshDashboardSnapshotJob::dispatch((int) $institutionId)->onQueue('crm-analytics');
        }

        $this->info("Dispatched snapshot refresh for {$institutions->count()} institutions.");

        return self::SUCCESS;
    }
}
