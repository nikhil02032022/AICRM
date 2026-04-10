<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Models\CRM\DltTemplate;
use App\Enums\CRM\DltTemplateStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-CC-008 — DLT template registration workflow
final class DltTemplateService
{
    /**
     * BRD: CRM-CC-008 — Create a new DLT template (starts as DRAFT).
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $institutionId): DltTemplate
    {
        return DltTemplate::create([...$data, 'institution_id' => $institutionId, 'status' => DltTemplateStatus::DRAFT]);
    }

    /**
     * BRD: CRM-CC-008 — Submit template for TRAI/gateway DLT approval.
     */
    public function submitForApproval(DltTemplate $template): DltTemplate
    {
        $template->update([
            'status'       => DltTemplateStatus::PENDING_APPROVAL,
            'submitted_at' => now(),
        ]);

        return $template->refresh();
    }

    /**
     * BRD: CRM-CC-008 — Mark a DLT template as approved after gateway confirmation.
     */
    public function markApproved(DltTemplate $template, string $dltId): DltTemplate
    {
        $template->update([
            'status'         => DltTemplateStatus::APPROVED,
            'dlt_template_id'=> $dltId,
            'approved_at'    => now(),
        ]);

        return $template->refresh();
    }

    /**
     * BRD: CRM-CC-008 — Mark a DLT template as rejected with reason notes.
     */
    public function markRejected(DltTemplate $template, string $notes): DltTemplate
    {
        $template->update([
            'status'         => DltTemplateStatus::REJECTED,
            'approval_notes' => $notes,
        ]);

        return $template->refresh();
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = DltTemplate::query();

        if (! empty($filters['gateway'])) {
            $query->where('gateway', $filters['gateway']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
