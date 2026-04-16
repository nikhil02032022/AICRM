<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\OfferLetter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OfferLetterRepositoryInterface
{
    public function create(array $data): OfferLetter;

    public function findByUuidOrFail(string $uuid): OfferLetter;

    public function findByApplicationUuid(string $applicationUuid): ?OfferLetter;

    /** @param array<string, mixed> $data */
    public function update(OfferLetter $offerLetter, array $data): OfferLetter;

    public function softDelete(OfferLetter $offerLetter): void;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;
}
