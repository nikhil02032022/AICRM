<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ApplicationRepositoryInterface
{
    public function create(array $data): Application;

    public function findByUuidOrFail(string $uuid): Application;

    public function findByLeadUuidOrFail(string $leadUuid): ?Application;

    /** @param array<string, mixed> $data */
    public function update(Application $application, array $data): Application;

    public function softDelete(Application $application): void;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /** @param array<string, mixed> $filters */
    public function all(array $filters = []): \Illuminate\Database\Eloquent\Collection;
}
