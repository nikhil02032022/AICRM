<?php

declare(strict_types=1);

namespace App\Services\CRM\Admin;

use App\Enums\CRM\WorkflowTemplateCategory;
use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\WorkflowTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

// BRD: CRM-SA-007 — Workflow automation template library management
final class WorkflowTemplateService
{
    /**
     * BRD: CRM-SA-007 — List all templates visible to an institution (global + own).
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(int $institutionId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return WorkflowTemplate::withoutGlobalScopes()
            ->where(function ($q) use ($institutionId): void {
                $q->where('is_global', true)
                    ->orWhere('institution_id', $institutionId);
            })
            ->where('is_active', true)
            ->when(!empty($filters['category']), fn ($q) => $q->where('category', $filters['category']))
            ->when(!empty($filters['search']), fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%'))
            ->orderBy('is_global', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findByUuidOrFail(string $uuid): WorkflowTemplate
    {
        return WorkflowTemplate::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, int $institutionId): WorkflowTemplate
    {
        $data['institution_id'] = $institutionId;
        $data['is_global']      = false; // Institution users cannot create global templates

        return WorkflowTemplate::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(WorkflowTemplate $template, array $data): WorkflowTemplate
    {
        // Prevent institution users from marking templates as global
        unset($data['is_global']);
        $template->update($data);

        return $template->fresh();
    }

    public function delete(WorkflowTemplate $template): void
    {
        $template->delete();
    }

    /**
     * BRD: CRM-SA-007 — Clone a template into an AutomationWorkflow for the institution.
     */
    public function importAsWorkflow(WorkflowTemplate $template, int $institutionId, int $userId): AutomationWorkflow
    {
        $workflow = AutomationWorkflow::create([
            'institution_id' => $institutionId,
            'created_by'     => $userId,
            'name'           => $template->name . ' (imported)',
            'description'    => $template->description,
            'trigger_type'   => $template->trigger_type,
            'steps'          => $template->template_data['steps'] ?? [],
            'config'         => $template->template_data['config'] ?? [],
            'status'         => 'draft',
        ]);

        // Increment usage counter on template
        WorkflowTemplate::withoutGlobalScopes()
            ->where('id', $template->id)
            ->increment('used_count');

        return $workflow;
    }
}
