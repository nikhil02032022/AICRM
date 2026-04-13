<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Models\CRM\CallScript;
use App\Models\CRM\CallScriptStep;
use App\Repositories\CRM\Communication\CallScriptRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-TC-002 — Service for call script lifecycle and dynamic branch resolution
final class CallScriptService
{
    public function __construct(
        private readonly CallScriptRepositoryInterface $repository,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload, int $institutionId, int $createdBy): CallScript
    {
        return $this->repository->create($payload, $institutionId, $createdBy);
    }

    /** @param array<string, mixed> $payload */
    public function update(CallScript $script, array $payload): CallScript
    {
        return $this->repository->update($script, $payload);
    }

    public function delete(CallScript $script): void
    {
        $this->repository->softDelete($script);
    }

    public function firstStep(CallScript $script): ?CallScriptStep
    {
        return $this->repository->firstStep($script);
    }

    public function stepByKey(CallScript $script, string $stepKey): ?CallScriptStep
    {
        return $this->repository->findStepByKey($script, $stepKey);
    }

    public function resolveNextStep(CallScript $script, string $currentStepKey, mixed $response): ?CallScriptStep
    {
        $current = $this->repository->findStepByKey($script, $currentStepKey);
        if ($current === null || $current->is_terminal) {
            return null;
        }

        $nextStepKey = $this->evaluateBranchRules($current->branch_rules, $response)
            ?? $current->default_next_step_key;

        if ($nextStepKey === null || $nextStepKey === '') {
            return null;
        }

        return $this->repository->findStepByKey($script, $nextStepKey);
    }

    /**
     * @param array<int, array<string, mixed>>|null $rules
     */
    private function evaluateBranchRules(?array $rules, mixed $response): ?string
    {
        if ($rules === null || $rules === []) {
            return null;
        }

        foreach ($rules as $rule) {
            $operator = (string) ($rule['operator'] ?? 'equals');
            $expected = $rule['value'] ?? null;
            $nextStepKey = isset($rule['next_step_key']) ? (string) $rule['next_step_key'] : null;

            if ($nextStepKey === null || $nextStepKey === '') {
                continue;
            }

            $matched = match ($operator) {
                'contains' => str_contains(strtolower((string) $response), strtolower((string) $expected)),
                'gte' => is_numeric($response) && is_numeric($expected) && (float) $response >= (float) $expected,
                'lte' => is_numeric($response) && is_numeric($expected) && (float) $response <= (float) $expected,
                'truthy' => filter_var($response, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true,
                default => (string) $response === (string) $expected,
            };

            if ($matched) {
                return $nextStepKey;
            }
        }

        return null;
    }
}
