<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\DTOs\CRM\CreateApplicationFormTemplateDTO;
use App\Models\CRM\ApplicationFormTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ApplicationFormTemplateRepositoryInterface
{
    public function create(CreateApplicationFormTemplateDTO $dto, int $institutionId, ?int $createdBy = null): ApplicationFormTemplate;

    public function findByUuidOrFail(string $uuid): ApplicationFormTemplate;

    /** @param array<string, mixed> $data */
    public function update(ApplicationFormTemplate $template, array $data): ApplicationFormTemplate;

    public function softDelete(ApplicationFormTemplate $template): void;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function generateUniqueSlug(string $name, int $institutionId): string;
}
