<?php

declare(strict_types=1);

namespace App\Services\CRM\Portal;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

// BRD: CRM-SP-006 — Multiple simultaneous applications list and detail for the applicant portal
final class PortalApplicationService
{
    /**
     * @return Collection<int, array{application: Application, pending_docs: int, total_docs: int, payments: Collection, total_paid: float}>
     */
    public function list(Lead $lead, Institution $institution): Collection
    {
        $applications = Application::withoutGlobalScopes()
            ->where('lead_uuid', $lead->uuid)
            ->where('institution_id', $institution->id)
            ->with([
                'programme',
                'currentOfferLetter',
                'transactions' => fn ($q) => $q->where('status', PaymentStatus::SUCCESS->value),
                'documents',
            ])
            ->latest('submitted_at')
            ->get();

        return $applications->map(fn (Application $app) => $this->buildSummary($app, $institution));
    }

    /**
     * @return array{application: Application, pending_docs: int, total_docs: int, payments: Collection, total_paid: float, history: Collection, checklist: DocumentChecklist|null, uploaded_ids: Collection}
     * @throws AuthorizationException
     */
    public function detail(string $applicationUuid, Lead $lead, Institution $institution): array
    {
        $app = Application::withoutGlobalScopes()
            ->where('uuid', $applicationUuid)
            ->where('institution_id', $institution->id)
            ->with([
                'programme',
                'currentOfferLetter',
                'transactions' => fn ($q) => $q->where('status', PaymentStatus::SUCCESS->value),
                'documents',
                'statusHistory' => fn ($q) => $q->orderBy('created_at'),
            ])
            ->first();

        if ($app === null || $app->lead_uuid !== $lead->uuid) {
            throw new AuthorizationException('Application not found or access denied.');
        }

        $checklist = DocumentChecklist::withoutGlobalScopes()
            ->where('institution_id', $institution->id)
            ->where('programme_id', $app->programme_id)
            ->where('is_active', true)
            ->with('items')
            ->first();

        $uploadedItemIds = $app->documents
            ->pluck('document_checklist_item_id')
            ->filter()
            ->unique();

        $pendingDocs = 0;
        $totalDocs   = 0;

        if ($checklist !== null) {
            $mandatory   = $checklist->items->where('is_mandatory', true);
            $totalDocs   = $mandatory->count();
            $pendingDocs = $mandatory->reject(fn ($item) => $uploadedItemIds->contains($item->id))->count();
        }

        return [
            'application'  => $app,
            'pending_docs' => $pendingDocs,
            'total_docs'   => $totalDocs,
            'payments'     => $app->transactions,
            'total_paid'   => (float) $app->transactions->sum('amount'),
            'history'      => $app->statusHistory,
            'checklist'    => $checklist,
            'uploaded_ids' => $uploadedItemIds,
        ];
    }

    /** @return array{application: Application, pending_docs: int, total_docs: int, payments: Collection, total_paid: float} */
    private function buildSummary(Application $app, Institution $institution): array
    {
        $checklist = DocumentChecklist::withoutGlobalScopes()
            ->where('institution_id', $institution->id)
            ->where('programme_id', $app->programme_id)
            ->where('is_active', true)
            ->with('items')
            ->first();

        $uploadedItemIds = $app->documents
            ->pluck('document_checklist_item_id')
            ->filter()
            ->unique();

        $pendingDocs = 0;
        $totalDocs   = 0;

        if ($checklist !== null) {
            $mandatory   = $checklist->items->where('is_mandatory', true);
            $totalDocs   = $mandatory->count();
            $pendingDocs = $mandatory->reject(fn ($item) => $uploadedItemIds->contains($item->id))->count();
        }

        return [
            'application'  => $app,
            'pending_docs' => $pendingDocs,
            'total_docs'   => $totalDocs,
            'payments'     => $app->transactions,
            'total_paid'   => (float) $app->transactions->sum('amount'),
        ];
    }
}
