<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Enums\CRM\ReportDeliveryStatus;
use App\Enums\CRM\ReportFrequency;
use App\Models\CRM\CustomReport;
use App\Models\CRM\ReportDelivery;
use App\Models\CRM\ReportSchedule;
use App\Jobs\CRM\Analytics\ReportDeliveryJob;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-AR-020 — Service for managing scheduled report delivery configurations
final class ReportSchedulerService
{
    /** @param array<string, mixed> $filters */
    public function paginate(int $institutionId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return ReportSchedule::where('institution_id', $institutionId)
            ->with('customReport:id,uuid,name', 'createdBy:id,name')
            ->when(!empty($filters['search']), fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%'))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * BRD: CRM-AR-020 — Create and immediately calculate the first next_run_at.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $institutionId, int $userId): ReportSchedule
    {
        $data['institution_id'] = $institutionId;
        $data['created_by']     = $userId;
        $data['next_run_at']    = $this->calculateNextRun($data);

        return ReportSchedule::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(ReportSchedule $schedule, array $data): ReportSchedule
    {
        $data['next_run_at'] = $this->calculateNextRun(array_merge($schedule->toArray(), $data));
        $schedule->update($data);

        return $schedule->fresh();
    }

    public function delete(ReportSchedule $schedule): void
    {
        $schedule->delete();
    }

    /**
     * BRD: CRM-AR-020 — Queue delivery for a schedule immediately (manual trigger or scheduler).
     */
    public function dispatchDelivery(ReportSchedule $schedule): ReportDelivery
    {
        $delivery = ReportDelivery::create([
            'institution_id'    => $schedule->institution_id,
            'report_schedule_id'=> $schedule->id,
            'custom_report_id'  => $schedule->custom_report_id,
            'status'            => ReportDeliveryStatus::QUEUED,
            'recipient_emails'  => $schedule->recipient_emails,
            'format'            => $schedule->format->value,
        ]);

        ReportDeliveryJob::dispatch($delivery->id)
            ->onQueue('crm-analytics');

        return $delivery;
    }

    /**
     * BRD: CRM-AR-020 — Process all due schedules (called from scheduler).
     */
    public function processDueSchedules(): void
    {
        ReportSchedule::whereNull('deleted_at')
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->each(function (ReportSchedule $schedule): void {
                $this->dispatchDelivery($schedule);

                $schedule->update([
                    'last_sent_at' => now(),
                    'next_run_at'  => $this->calculateNextRun($schedule->toArray()),
                ]);
            });
    }

    /** @param array<string, mixed> $data */
    private function calculateNextRun(array $data): Carbon
    {
        $now       = now();
        $frequency = ReportFrequency::from($data['frequency']);
        [$hour, $minute] = explode(':', $data['run_time'] ?? '07:00');

        return match ($frequency) {
            ReportFrequency::DAILY   => $now->copy()->addDay()->setHour((int) $hour)->setMinute((int) $minute)->setSecond(0),
            ReportFrequency::WEEKLY  => $now->copy()->next((int) ($data['day_of_week'] ?? 1))->setHour((int) $hour)->setMinute((int) $minute)->setSecond(0),
            ReportFrequency::MONTHLY => $now->copy()->addMonth()->setDay((int) ($data['day_of_month'] ?? 1))->setHour((int) $hour)->setMinute((int) $minute)->setSecond(0),
        };
    }
}
