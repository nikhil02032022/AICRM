<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Analytics;

use App\Models\CRM\Analytics\DashboardMetricSnapshot;
use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AR-001, AR-006 — Nightly aggregation job that pre-builds dashboard_metric_snapshots for the previous day
final class RefreshDashboardSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 120;

    public function __construct(
        private readonly int   $institutionId,
        private readonly ?Carbon $forDate = null,
    ) {}

    public function handle(): void
    {
        $date = $this->forDate ?? now()->subDay()->startOfDay();

        Log::info('Refreshing dashboard snapshots', [
            'institution_id' => $this->institutionId,
            'period_date'    => $date->toDateString(),
        ]);

        $this->upsertSnapshot('leads_total', $this->countLeads($date));
        $this->upsertSnapshot('applications_total', $this->countApplications($date));
        $this->upsertSnapshot('enrolments_total', $this->countEnrolments($date));
        $this->upsertSnapshot('revenue_total', $this->sumRevenue($date));
    }

    private function countLeads(Carbon $date): float
    {
        return Lead::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->whereDate('created_at', $date)
            ->count();
    }

    private function countApplications(Carbon $date): float
    {
        return Application::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->whereDate('created_at', $date)
            ->count();
    }

    private function countEnrolments(Carbon $date): float
    {
        return Application::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->where('status', 'enrolled')
            ->whereDate('updated_at', $date)
            ->count();
    }

    private function sumRevenue(Carbon $date): float
    {
        return (float) PaymentTransaction::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->where('status', 'success')
            ->whereDate('confirmed_at', $date)
            ->sum('amount');
    }

    private function upsertSnapshot(string $metricKey, float $value): void
    {
        DashboardMetricSnapshot::updateOrCreate(
            [
                'institution_id' => $this->institutionId,
                'campus_id'      => null,
                'period_date'    => ($this->forDate ?? now()->subDay())->toDateString(),
                'metric_key'     => $metricKey,
            ],
            ['metric_value' => $value],
        );
    }
}
