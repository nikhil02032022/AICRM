<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Analytics;

use App\Enums\CRM\LeadSource;
use App\Http\Controllers\Controller;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Lead;
use App\Services\CRM\Analytics\DashboardScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-AR-008 — Drill-down from dashboard metric tiles to the underlying lead records
final class DrillDownController extends Controller
{
    public function __construct(
        private readonly DashboardScopeService $scopeService,
    ) {}

    public function leads(Request $request): View
    {
        Gate::authorize('crm.analytics.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        // Whitelist metric param — never trust free-text input for query branching
        $metric = $request->input('metric', 'leads');
        if (!in_array($metric, ['leads', 'applications', 'offers', 'enrolments'], strict: true)) {
            $metric = 'leads';
        }

        // Validate source against enum
        $source = $request->input('source');
        if ($source !== null && LeadSource::tryFrom($source) === null) {
            $source = null;
        }

        // Validate programme_id belongs to this institution before using it
        $programmeId   = (int) $request->input('programme_id', 0) ?: null;
        $programmeName = null;
        if ($programmeId !== null) {
            $programme = CrmProgramme::withoutGlobalScopes()
                ->where('id', $programmeId)
                ->where('institution_id', $scope['institution_id'])
                ->first(['id', 'name']);

            if ($programme === null) {
                $programmeId = null;
            } else {
                $programmeName = $programme->name;
            }
        }

        $statuses = match ($metric) {
            'applications' => ['application_submitted', 'offer_issued', 'fee_paid', 'enrolled', 'deferred'],
            'offers'       => ['offer_issued', 'fee_paid', 'enrolled'],
            'enrolments'   => ['enrolled'],
            default        => null,
        };

        $leads = Lead::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'], fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($statuses, fn ($q) => $q->whereIn('status', $statuses))
            ->when($source, fn ($q) => $q->where('source', $source))
            ->when($programmeId, fn ($q) => $q->whereHas(
                'programmeInterests',
                fn ($pq) => $pq->where('crm_programme_id', $programmeId)->wherePivot('is_primary', true),
            ))
            ->with([
                'assignedCounsellor:id,name',
                'programmeInterests' => fn ($pq) => $pq->wherePivot('is_primary', true),
            ])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $metricLabels = [
            'leads'        => 'All Leads',
            'applications' => 'Applications',
            'offers'       => 'Offers Issued',
            'enrolments'   => 'Enrolments',
        ];

        $context = [
            'metric'        => $metric,
            'metric_label'  => $metricLabels[$metric],
            'from'          => $from,
            'to'            => $to,
            'source'        => $source,
            'programme_id'  => $programmeId,
            'programme_name'=> $programmeName,
        ];

        return view('crm.analytics.drill-down.leads', compact('leads', 'context'));
    }
}
