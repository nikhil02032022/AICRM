<?php

declare(strict_types=1);

namespace App\Services\CRM\Portal;

use App\Enums\CRM\CounsellingSessionStatus;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Illuminate\Support\Collection;

// BRD: CRM-SP-003 — Aggregate application status, documents, payments, and appointments for dashboard
final class PortalDashboardService
{
    /**
     * @return array{applicationData: Collection, upcomingAppointments: Collection, lead: Lead}
     */
    public function getData(Lead $lead, Institution $institution): array
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

        $applicationData = $applications->map(
            fn (Application $app) => $this->buildApplicationData($app, $institution)
        );

        $upcomingAppointments = CounsellingSession::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->where('institution_id', $institution->id)
            ->where('scheduled_at', '>', now())
            ->whereNotIn('status', [
                CounsellingSessionStatus::CANCELLED->value,
                CounsellingSessionStatus::COMPLETED->value,
                CounsellingSessionStatus::NO_SHOW->value,
            ])
            ->with('counsellor')
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        return [
            'applicationData'      => $applicationData,
            'upcomingAppointments' => $upcomingAppointments,
            'lead'                 => $lead,
        ];
    }

    /** @return array{application: Application, pending_docs: int, total_docs: int, payments: Collection, total_paid: float|int} */
    private function buildApplicationData(Application $app, Institution $institution): array
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
            'application' => $app,
            'pending_docs' => $pendingDocs,
            'total_docs'   => $totalDocs,
            'payments'     => $app->transactions,
            'total_paid'   => (float) $app->transactions->sum('amount'),
        ];
    }
}
