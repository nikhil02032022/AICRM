<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\ApplicationConversionLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentApplicationConversionLogRepository implements ApplicationConversionLogRepositoryInterface
{
    public function create(array $data): ApplicationConversionLog
    {
        return ApplicationConversionLog::create($data);
    }

    public function findByUuidOrFail(string $uuid): ApplicationConversionLog
    {
        return ApplicationConversionLog::whereUuid($uuid)->firstOrFail();
    }

    public function findByApplicationUuid(string $applicationUuid): ?ApplicationConversionLog
    {
        return ApplicationConversionLog::whereApplicationUuid($applicationUuid)
            ->orderByDesc('created_at')
            ->first();
    }

    public function update(ApplicationConversionLog $log, array $data): ApplicationConversionLog
    {
        $log->update($data);

        return $log->refresh();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = ApplicationConversionLog::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('completed_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('completed_at', '<=', $filters['to_date']);
        }

        return $query->with(['application', 'lead', 'convertedBy'])
            ->orderByDesc('completed_at')
            ->paginate($perPage);
    }

    public function findRetryable(): \Illuminate\Database\Eloquent\Collection
    {
        return ApplicationConversionLog::where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->get();
    }
}
