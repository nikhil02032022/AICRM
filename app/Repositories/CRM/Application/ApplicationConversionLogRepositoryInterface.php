<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\ApplicationConversionLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ApplicationConversionLogRepositoryInterface
{
    public function create(array $data): ApplicationConversionLog;

    public function findByUuidOrFail(string $uuid): ApplicationConversionLog;

    public function findByApplicationUuid(string $applicationUuid): ?ApplicationConversionLog;

    /** @param array<string, mixed> $data */
    public function update(ApplicationConversionLog $log, array $data): ApplicationConversionLog;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find all conversion logs that are eligible for retry.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findRetryable(): \Illuminate\Database\Eloquent\Collection;
}
