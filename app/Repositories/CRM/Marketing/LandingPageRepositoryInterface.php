<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\DTOs\CRM\CreateLandingPageDTO;
use App\Models\CRM\LandingPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LandingPageRepositoryInterface
{
    public function create(CreateLandingPageDTO $dto, int $institutionId, int $userId): LandingPage;

    /** @param array<string, mixed> $data */
    public function update(LandingPage $landingPage, array $data): LandingPage;

    public function softDelete(LandingPage $landingPage): void;

    public function findByUuidOrFail(string $uuid): LandingPage;

    public function findPublishedBySlug(string $slug): ?LandingPage;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function generateUniqueSlug(string $name, int $institutionId): string;
}