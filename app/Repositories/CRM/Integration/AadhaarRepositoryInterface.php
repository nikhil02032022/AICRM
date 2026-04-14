<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Integration;

use App\Models\CRM\AadhaarEkycLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-DM-007 — Aadhaar eKYC log repository interface
interface AadhaarRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?AadhaarEkycLog;

    public function findByLead(int $leadId, int $institutionId): Collection;

    public function create(array $data): AadhaarEkycLog;

    public function update(AadhaarEkycLog $log, array $data): AadhaarEkycLog;
}
