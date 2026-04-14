<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Integration;

use App\Models\CRM\AlumniBridgeLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-EI-008 — Alumni bridge log repository interface
interface AlumniBridgeRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?AlumniBridgeLog;

    public function findByErpStudentId(string $erpStudentId, int $institutionId): ?AlumniBridgeLog;

    public function create(array $data): AlumniBridgeLog;

    public function update(AlumniBridgeLog $log, array $data): AlumniBridgeLog;
}
