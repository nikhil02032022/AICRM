<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Integration;

use App\Models\CRM\DigiLockerDocument;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-DM-006 — DigiLocker document repository interface
interface DigiLockerRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?DigiLockerDocument;

    public function findByLead(int $leadId, int $institutionId): \Illuminate\Database\Eloquent\Collection;

    public function create(array $data): DigiLockerDocument;

    public function update(DigiLockerDocument $document, array $data): DigiLockerDocument;
}
