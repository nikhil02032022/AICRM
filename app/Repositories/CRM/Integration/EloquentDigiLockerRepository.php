<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Integration;

use App\Models\CRM\DigiLockerDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-DM-006 — Eloquent implementation of DigiLocker document repository
final class EloquentDigiLockerRepository implements DigiLockerRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return DigiLockerDocument::where('institution_id', $institutionId)
            ->with('lead')
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?DigiLockerDocument
    {
        return DigiLockerDocument::where('uuid', $uuid)->with('lead')->first();
    }

    public function findByLead(int $leadId, int $institutionId): Collection
    {
        return DigiLockerDocument::where('institution_id', $institutionId)
            ->where('lead_id', $leadId)
            ->latest()
            ->get();
    }

    public function create(array $data): DigiLockerDocument
    {
        return DigiLockerDocument::create($data);
    }

    public function update(DigiLockerDocument $document, array $data): DigiLockerDocument
    {
        $document->update($data);

        return $document->refresh();
    }
}
