<?php

declare(strict_types=1);

namespace App\Services\CRM\Application;

use App\DTOs\CRM\CreateApplicationFormTemplateDTO;
use App\Models\CRM\ApplicationFormTemplate;
use App\Repositories\CRM\Application\ApplicationFormTemplateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-AP-001 — Service orchestration for configurable multi-step application form templates
final class ApplicationFormBuilderService
{
    public function __construct(
        private readonly ApplicationFormTemplateRepositoryInterface $repository,
    ) {}

    public function create(CreateApplicationFormTemplateDTO $dto, int $institutionId, ?int $actorId = null): ApplicationFormTemplate
    {
        return $this->repository->create($dto, $institutionId, $actorId);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(ApplicationFormTemplate $template, array $validated): ApplicationFormTemplate
    {
        return $this->repository->update($template, $validated);
    }

    public function delete(ApplicationFormTemplate $template): void
    {
        $this->repository->softDelete($template);
    }

    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function generateUniqueSlug(string $name, int $institutionId): string
    {
        return $this->repository->generateUniqueSlug($name, $institutionId);
    }
}
