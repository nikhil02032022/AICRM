<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Analytics;

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\Agents\AgentStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LostReason;
use App\Enums\CRM\Payments\FeeType;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\CRM\Campus;
use App\Models\CRM\CrmProgramme;
use App\Models\User;
use App\Services\CRM\Analytics\DashboardScopeService;
use App\Services\CRM\Analytics\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-AR-009 to CRM-AR-017 — Standard report controllers, one public method per report
final class ReportController extends Controller
{
    public function __construct(
        private readonly DashboardScopeService $scopeService,
        private readonly ReportService         $reportService,
    ) {}

    // BRD: CRM-AR-009 — Enquiry Register: full lead list filtered by date, source, status, campus, counsellor
    public function enquiryRegister(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from'          => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'            => $request->input('to', now()->toDateString()),
            'source'        => $request->input('source'),
            'status'        => $request->input('status'),
            'campus_id'     => $request->input('campus_id'),
            'counsellor_id' => $request->input('counsellor_id'),
        ];

        $leads = $this->reportService->enquiryRegister($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $counsellors = $scope['role'] !== 'counsellor'
            ? User::role(['counsellor', 'senior-counsellor'])
                ->where('institution_id', $scope['institution_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $sources  = LeadSource::cases();
        $statuses = LeadStatus::cases();

        return view('crm.analytics.reports.enquiry-register', compact(
            'leads', 'filters', 'campuses', 'counsellors', 'sources', 'statuses', 'scope',
        ));
    }

    // BRD: CRM-AR-010 — Counsellor Activity: per-counsellor summary of leads, tasks, calls, sessions
    public function counsellorActivity(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from'          => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'            => $request->input('to', now()->toDateString()),
            'campus_id'     => $request->input('campus_id'),
            'counsellor_id' => $request->input('counsellor_id'),
        ];

        $rows = $this->reportService->counsellorActivity($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $counsellors = $scope['role'] !== 'counsellor'
            ? User::role(['counsellor', 'senior-counsellor'])
                ->where('institution_id', $scope['institution_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return view('crm.analytics.reports.counsellor-activity', compact(
            'rows', 'filters', 'campuses', 'counsellors', 'scope',
        ));
    }

    // BRD: CRM-AR-011 — Application Status: paginated application list with pipeline stage and counsellor
    public function applicationStatus(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from'          => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'            => $request->input('to', now()->toDateString()),
            'status'        => $request->input('status'),
            'programme_id'  => $request->input('programme_id'),
            'campus_id'     => $request->input('campus_id'),
            'counsellor_id' => $request->input('counsellor_id'),
        ];

        $applications = $this->reportService->applicationStatus($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $programmes = CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $counsellors = $scope['role'] !== 'counsellor'
            ? User::role(['counsellor', 'senior-counsellor'])
                ->where('institution_id', $scope['institution_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $statuses = ApplicationStatus::cases();

        return view('crm.analytics.reports.application-status', compact(
            'applications', 'filters', 'campuses', 'programmes', 'counsellors', 'statuses', 'scope',
        ));
    }

    // BRD: CRM-AR-013 — Lost Lead Analysis: paginated lost leads with reason, source, counsellor, and days-to-loss
    public function lostLeadAnalysis(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from'          => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'            => $request->input('to', now()->toDateString()),
            'source'        => $request->input('source'),
            'lost_reason'   => $request->input('lost_reason'),
            'campus_id'     => $request->input('campus_id'),
            'counsellor_id' => $request->input('counsellor_id'),
        ];

        $leads         = $this->reportService->lostLeadAnalysis($scope, $filters);
        $reasonSummary = $this->reportService->lostLeadsByReason($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $counsellors = $scope['role'] !== 'counsellor'
            ? User::role(['counsellor', 'senior-counsellor'])
                ->where('institution_id', $scope['institution_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $sources     = LeadSource::cases();
        $lostReasons = LostReason::cases();

        return view('crm.analytics.reports.lost-lead-analysis', compact(
            'leads', 'reasonSummary', 'filters', 'campuses', 'counsellors', 'sources', 'lostReasons', 'scope',
        ));
    }

    // BRD: CRM-AR-014 — Fee Collection: paginated payment transactions with student, programme, fee type, amount, status
    public function feeCollection(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from'          => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'            => $request->input('to', now()->toDateString()),
            'status'        => $request->input('status'),
            'fee_type'      => $request->input('fee_type'),
            'campus_id'     => $request->input('campus_id'),
            'counsellor_id' => $request->input('counsellor_id'),
            'programme_id'  => $request->input('programme_id'),
        ];

        $transactions = $this->reportService->feeCollection($scope, $filters);
        $summary      = $this->reportService->feeCollectionSummary($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $programmes = CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $counsellors = $scope['role'] !== 'counsellor'
            ? User::role(['counsellor', 'senior-counsellor'])
                ->where('institution_id', $scope['institution_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $feeTypes = FeeType::cases();
        $statuses = PaymentStatus::cases();

        return view('crm.analytics.reports.fee-collection', compact(
            'transactions', 'summary', 'filters', 'campuses', 'programmes',
            'counsellors', 'feeTypes', 'statuses', 'scope',
        ));
    }

    // BRD: CRM-AR-015 — Document Compliance: per-application document status breakdown
    public function documentCompliance(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from'          => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'            => $request->input('to', now()->toDateString()),
            'compliance'    => $request->input('compliance'),
            'programme_id'  => $request->input('programme_id'),
            'campus_id'     => $request->input('campus_id'),
            'counsellor_id' => $request->input('counsellor_id'),
        ];

        $applications = $this->reportService->documentCompliance($scope, $filters);
        $summary      = $this->reportService->documentComplianceSummary($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $programmes = CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $counsellors = $scope['role'] !== 'counsellor'
            ? User::role(['counsellor', 'senior-counsellor'])
                ->where('institution_id', $scope['institution_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return view('crm.analytics.reports.document-compliance', compact(
            'applications', 'summary', 'filters', 'campuses', 'programmes', 'counsellors', 'scope',
        ));
    }

    // BRD: CRM-AR-016 — Year-on-Year Comparison: current vs previous year KPIs and per-dimension breakdown
    public function yearOnYear(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'year'          => $request->input('year', (string) now()->year),
            'group_by'      => $request->input('group_by', 'programme'),
            'campus_id'     => $request->input('campus_id'),
        ];

        $summary   = $this->reportService->yearOnYearSummary($scope, $filters);
        $breakdown = $this->reportService->yearOnYearBreakdown($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        // Year options: current year and 4 prior years
        $years = collect(range(now()->year, now()->year - 4));

        return view('crm.analytics.reports.year-on-year', compact(
            'summary', 'breakdown', 'filters', 'campuses', 'years', 'scope',
        ));
    }

    // BRD: CRM-AR-017 — Agent Performance: per-agent referred leads, funnel conversion, and commission summary
    public function agentPerformance(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from'         => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'           => $request->input('to', now()->toDateString()),
            'agent_status' => $request->input('agent_status'),
            'campus_id'    => $request->input('campus_id'),
        ];

        $rows = $this->reportService->agentPerformance($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $agentStatuses = AgentStatus::cases();

        return view('crm.analytics.reports.agent-performance', compact(
            'rows', 'filters', 'campuses', 'agentStatuses', 'scope',
        ));
    }

    // BRD: CRM-AR-012 — Source Effectiveness: per-source funnel (leads → applied → offered → enrolled)
    public function sourceEffectiveness(Request $request): View
    {
        Gate::authorize('crm.reports.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from'      => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'        => $request->input('to', now()->toDateString()),
            'campus_id' => $request->input('campus_id'),
        ];

        $rows = $this->reportService->sourceEffectiveness($scope, $filters);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('crm.analytics.reports.source-effectiveness', compact(
            'rows', 'filters', 'campuses', 'scope',
        ));
    }
}
