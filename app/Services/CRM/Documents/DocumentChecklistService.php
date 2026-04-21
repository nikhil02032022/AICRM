<?php

declare(strict_types=1);

namespace App\Services\CRM\Documents;

use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Documents\DocumentChecklistItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// BRD: CRM-DM-001 — Programme-wise document checklist configuration.
final class DocumentChecklistService
{
    /**
     * @param array<string,mixed> $data
     * @param array<int, array<string,mixed>> $items
     */
    public function create(array $data, array $items = []): DocumentChecklist
    {
        $data['institution_id'] ??= Auth::user()?->institution_id;
        $data['created_by']     ??= Auth::id();

        return DB::transaction(function () use ($data, $items): DocumentChecklist {
            $checklist = DocumentChecklist::create($data);
            foreach ($items as $idx => $item) {
                $this->addItem($checklist, $item + ['sort_order' => $idx]);
            }

            return $checklist->fresh('items');
        });
    }

    /** @param array<string,mixed> $data */
    public function update(DocumentChecklist $checklist, array $data): DocumentChecklist
    {
        $data['updated_by'] = Auth::id();
        $checklist->fill($data)->save();

        return $checklist->fresh('items');
    }

    /** @param array<string,mixed> $data */
    public function addItem(DocumentChecklist $checklist, array $data): DocumentChecklistItem
    {
        $data['institution_id'] = $checklist->institution_id;
        $data['document_checklist_id'] = $checklist->id;

        return DocumentChecklistItem::create($data);
    }

    public function removeItem(DocumentChecklistItem $item): void
    {
        $item->delete();
    }

    public function toggle(DocumentChecklist $checklist): DocumentChecklist
    {
        $checklist->is_active = ! $checklist->is_active;
        $checklist->updated_by = Auth::id();
        $checklist->save();

        return $checklist;
    }

    public function resolveForProgramme(int $institutionId, ?int $programmeId): ?DocumentChecklist
    {
        return DocumentChecklist::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->where(function ($q) use ($programmeId): void {
                $q->where('programme_id', $programmeId)->orWhereNull('programme_id');
            })
            ->orderByRaw('programme_id IS NULL ASC')
            ->with('items')
            ->first();
    }
}
