<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\WorkflowNodeType;
use App\Enums\CRM\WorkflowStatus;

// BRD: CRM-MA-001 — DTO for creating/updating visual automation workflows
final readonly class CreateAutomationWorkflowDTO
{
    /**
     * @param  array<string, mixed>|null  $triggerConfig
     * @param  array<int, array<string, mixed>>  $steps
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public WorkflowStatus $status,
        public string $triggerType,
        public ?array $triggerConfig,
        public array $steps,
        public ?int $campusId,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        $steps = [];

        if (isset($validated['steps']) && is_array($validated['steps'])) {
            $steps = array_values(array_map(static function (array $step, int $index): array {
                $type = isset($step['node_type']) && is_string($step['node_type'])
                    ? WorkflowNodeType::from($step['node_type'])
                    : WorkflowNodeType::ACTION;

                return [
                    'id' => isset($step['id']) && is_string($step['id']) ? $step['id'] : 'step-'.$index,
                    'order' => isset($step['order']) ? (int) $step['order'] : $index,
                    'node_type' => $type,
                    'name' => (string) ($step['name'] ?? 'Step '.($index + 1)),
                    'config' => isset($step['config']) && is_array($step['config']) ? $step['config'] : null,
                    'delay_minutes' => isset($step['delay_minutes']) ? (int) $step['delay_minutes'] : null,
                ];
            }, $validated['steps'], array_keys($validated['steps'])));
        }

        return new self(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            status: WorkflowStatus::from($validated['status'] ?? WorkflowStatus::DRAFT->value),
            triggerType: $validated['trigger_type'],
            triggerConfig: isset($validated['trigger_config']) && is_array($validated['trigger_config']) ? $validated['trigger_config'] : null,
            steps: $steps,
            campusId: isset($validated['campus_id']) ? (int) $validated['campus_id'] : null,
        );
    }
}
