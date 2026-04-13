<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\CallDisposition;
use App\Models\CRM\CallDispositionConfig;
use App\Repositories\CRM\Communication\CallDispositionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// BRD: CRM-TC-003, CRM-TC-004 — Configurable dispositions and follow-up behavior lookup
final class CallDispositionService
{
    public function __construct(
        private readonly CallDispositionRepositoryInterface $repository,
    ) {}

    public function ensureDefaults(int $institutionId, int $userId): void
    {
        foreach (CallDisposition::cases() as $index => $disposition) {
            $existing = $this->repository->findByCode($institutionId, $disposition->value);
            if ($existing !== null) {
                continue;
            }

            $this->repository->create($institutionId, $userId, [
                'code' => $disposition->value,
                'label' => $disposition->label(),
                'is_active' => true,
                'requires_follow_up' => $disposition->requiresFollowUpTask(),
                'sort_order' => $index + 1,
                'is_system' => true,
            ]);
        }
    }

    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($institutionId, $perPage);
    }

    /** @return Collection<int, CallDispositionConfig> */
    public function activeForInstitution(int $institutionId): Collection
    {
        return $this->repository->activeForInstitution($institutionId);
    }

    public function shouldPromptFollowUp(int $institutionId, string $code): bool
    {
        $config = $this->repository->findByCode($institutionId, $code);

        return $config?->is_active === true && $config->requires_follow_up === true;
    }

    public function labelForCode(int $institutionId, string $code): ?string
    {
        return $this->repository->findByCode($institutionId, $code)?->label;
    }

    /** @param array<string, mixed> $payload */
    public function create(int $institutionId, int $userId, array $payload): CallDispositionConfig
    {
        return $this->repository->create($institutionId, $userId, $payload);
    }

    /** @param array<string, mixed> $payload */
    public function update(CallDispositionConfig $config, array $payload): CallDispositionConfig
    {
        return $this->repository->update($config, $payload);
    }
}
