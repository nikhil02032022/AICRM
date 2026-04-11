<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\DTOs\CRM\CreateAutomationWorkflowDTO;
use App\Models\CRM\AutomationWorkflow;
use App\Repositories\CRM\Marketing\AutomationWorkflowRepositoryInterface;
use Illuminate\Support\Facades\Log;

// BRD: CRM-MA-001 — Service for visual workflow builder CRUD orchestration
final class AutomationWorkflowService
{
    public function __construct(
        private readonly AutomationWorkflowRepositoryInterface $repository,
    ) {}

    public function create(CreateAutomationWorkflowDTO $dto, int $institutionId, int $userId): AutomationWorkflow
    {
        $workflow = $this->repository->create($dto, $institutionId, $userId);

        Log::info('Automation workflow created', [
            'workflow_uuid' => $workflow->uuid,
            'institution_id' => $workflow->institution_id,
            'user_id' => $userId,
        ]);

        return $workflow;
    }

    /** @param array<string, mixed> $data */
    public function update(AutomationWorkflow $workflow, array $data): AutomationWorkflow
    {
        if (isset($data['steps']) && is_array($data['steps'])) {
            $data['steps'] = array_values(array_map(static function (array $step, int $index): array {
                $step['order'] = isset($step['order']) ? (int) $step['order'] : $index;

                return $step;
            }, $data['steps'], array_keys($data['steps'])));
        }

        return $this->repository->update($workflow, $data);
    }

    public function delete(AutomationWorkflow $workflow): void
    {
        $this->repository->softDelete($workflow);
    }
}
