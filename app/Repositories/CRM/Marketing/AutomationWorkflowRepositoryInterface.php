<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\DTOs\CRM\CreateAutomationWorkflowDTO;
use App\Models\CRM\AutomationWorkflow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AutomationWorkflowRepositoryInterface
{
    public function create(CreateAutomationWorkflowDTO $dto, int $institutionId, int $userId): AutomationWorkflow;

    /** @param array<string, mixed> $data */
    public function update(AutomationWorkflow $workflow, array $data): AutomationWorkflow;

    public function softDelete(AutomationWorkflow $workflow): void;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;
}
