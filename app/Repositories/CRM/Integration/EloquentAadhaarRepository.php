<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Integration;

use App\Models\CRM\AadhaarEkycLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-DM-007 — Eloquent implementation of Aadhaar eKYC log repository
final class EloquentAadhaarRepository implements AadhaarRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return AadhaarEkycLog::where('institution_id', $institutionId)
            ->with('lead')
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?AadhaarEkycLog
    {
        return AadhaarEkycLog::where('uuid', $uuid)->with('lead')->first();
    }

    public function findByLead(int $leadId, int $institutionId): Collection
    {
        return AadhaarEkycLog::where('institution_id', $institutionId)
            ->where('lead_id', $leadId)
            ->latest()
            ->get();
    }

    public function create(array $data): AadhaarEkycLog
    {
        return AadhaarEkycLog::create($data);
    }

    public function update(AadhaarEkycLog $log, array $data): AadhaarEkycLog
    {
        $log->update($data);

        return $log->refresh();
    }
}
