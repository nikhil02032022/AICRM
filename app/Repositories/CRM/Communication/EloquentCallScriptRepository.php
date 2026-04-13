<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Models\CRM\CallScript;
use App\Models\CRM\CallScriptStep;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// BRD: CRM-TC-002 — Eloquent persistence for call scripts and branching steps
final class EloquentCallScriptRepository implements CallScriptRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return CallScript::query()
            ->withCount('steps')
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', function ($query) use ($filters): void {
                $query->where('status', (string) $filters['status']);
            })
            ->when(($filters['search'] ?? null) !== null && $filters['search'] !== '', function ($query) use ($filters): void {
                $query->where('name', 'like', '%'.(string) $filters['search'].'%');
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $payload, int $institutionId, int $createdBy): CallScript
    {
        return DB::transaction(function () use ($payload, $institutionId, $createdBy): CallScript {
            $script = CallScript::withoutGlobalScopes()->create([
                'uuid' => (string) Str::uuid(),
                'institution_id' => $institutionId,
                'campus_id' => $payload['campus_id'] ?? null,
                'name' => $payload['name'],
                'status' => $payload['status'] ?? 'draft',
                'description' => $payload['description'] ?? null,
                'is_default' => (bool) ($payload['is_default'] ?? false),
                'created_by' => $createdBy,
            ]);

            $this->syncSteps($script, $payload['steps'] ?? []);

            return $script->fresh('steps');
        });
    }

    public function update(CallScript $script, array $payload): CallScript
    {
        return DB::transaction(function () use ($script, $payload): CallScript {
            $script->update([
                'name' => $payload['name'],
                'status' => $payload['status'] ?? 'draft',
                'description' => $payload['description'] ?? null,
                'campus_id' => $payload['campus_id'] ?? null,
                'is_default' => (bool) ($payload['is_default'] ?? false),
            ]);

            $this->syncSteps($script, $payload['steps'] ?? []);

            return $script->fresh('steps');
        });
    }

    public function softDelete(CallScript $script): void
    {
        $script->delete();
    }

    public function findStepByKey(CallScript $script, string $stepKey): ?CallScriptStep
    {
        return CallScriptStep::query()
            ->where('call_script_id', $script->id)
            ->where('step_key', $stepKey)
            ->first();
    }

    public function firstStep(CallScript $script): ?CallScriptStep
    {
        return CallScriptStep::query()
            ->where('call_script_id', $script->id)
            ->orderBy('step_order')
            ->first();
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     */
    private function syncSteps(CallScript $script, array $steps): void
    {
        CallScriptStep::withoutGlobalScopes()
            ->where('call_script_id', $script->id)
            ->delete();

        foreach ($steps as $index => $step) {
            CallScriptStep::withoutGlobalScopes()->create([
                'uuid' => (string) Str::uuid(),
                'institution_id' => $script->institution_id,
                'campus_id' => $script->campus_id,
                'call_script_id' => $script->id,
                'step_key' => (string) $step['step_key'],
                'step_order' => isset($step['step_order']) ? (int) $step['step_order'] : $index + 1,
                'prompt_text' => (string) $step['prompt_text'],
                'response_type' => (string) $step['response_type'],
                'options' => $step['options'] ?? null,
                'branch_rules' => $step['branch_rules'] ?? null,
                'default_next_step_key' => $step['default_next_step_key'] ?? null,
                'is_terminal' => (bool) ($step['is_terminal'] ?? false),
            ]);
        }
    }
}
