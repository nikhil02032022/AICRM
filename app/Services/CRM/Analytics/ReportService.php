<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Models\CRM\Application;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionAccrual;
use App\Models\CRM\CallLog;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// BRD: CRM-AR-009 to CRM-AR-017 — Standard report query builders, one method per report type
final class ReportService
{
    public function __construct(
        private readonly DashboardScopeService $scopeService,
    ) {}

    /**
     * AR-009 — Enquiry Register: paginated lead list with key enquiry fields.
     *
     * Scope rules (same as all analytics):
     *  - counsellor  → own leads only
     *  - manager     → campus leads
     *  - director    → institution-wide
     *
     * Supported $filters keys:
     *  from, to, source (LeadSource value), status (LeadStatus value),
     *  campus_id (int, director/manager override), counsellor_id (int, manager/director override)
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function enquiryRegister(array $scope, array $filters): LengthAwarePaginator
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        return Lead::withoutGlobalScopes()
            ->with([
                'assignedCounsellor:id,name',
                'campus:id,name',
                'programmeInterests' => fn ($q) => $q
                    ->wherePivot('is_primary', true)
                    ->select('crm_programmes.id', 'crm_programmes.name'),
            ])
            ->where('institution_id', $scope['institution_id'])
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'], fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('assigned_counsellor_id', $filters['counsellor_id'])
            )
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * AR-009 export variant — returns up to 10 000 leads as a flat Collection (no pagination).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, Lead>
     */
    public function enquiryRegisterForExport(array $scope, array $filters): Collection
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        return Lead::withoutGlobalScopes()
            ->with([
                'assignedCounsellor:id,name',
                'campus:id,name',
                'programmeInterests' => fn ($q) => $q
                    ->wherePivot('is_primary', true)
                    ->select('crm_programmes.id', 'crm_programmes.name'),
            ])
            ->where('institution_id', $scope['institution_id'])
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'], fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('assigned_counsellor_id', $filters['counsellor_id'])
            )
            ->orderByDesc('created_at')
            ->limit(10000)
            ->get();
    }

    /**
     * AR-010 — Counsellor Activity Report: per-counsellor summary of leads, tasks,
     * calls and sessions for the selected date range.
     *
     * Scope rules:
     *  - counsellor  → own row only
     *  - manager     → campus counsellors
     *  - director    → institution-wide
     *
     * Supported $filters keys:
     *  from, to, campus_id (director override), counsellor_id (manager/director override)
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, User>
     */
    public function counsellorActivity(array $scope, array $filters): Collection
    {
        $from = ($filters['from'] ?? now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $to   = ($filters['to']   ?? now()->toDateString()) . ' 23:59:59';
        $iid  = $scope['institution_id'];

        return User::withoutGlobalScopes()
            ->role(['counsellor', 'senior-counsellor'])
            ->where('users.institution_id', $iid)
            ->when($scope['campus_id'],       fn ($q) => $q->where('users.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'],  fn ($q) => $q->whereIn('users.id', $scope['counsellor_ids']))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('users.campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('users.id', $filters['counsellor_id'])
            )
            ->addSelect([
                'users.id',
                'users.name',
                'users.campus_id',
                // new leads assigned in period
                'new_leads' => Lead::selectRaw('COUNT(*)')
                    ->withoutGlobalScopes()
                    ->whereColumn('assigned_counsellor_id', 'users.id')
                    ->where('institution_id', $iid)
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNull('deleted_at'),
                // leads converted (fee paid or enrolled) that were created in period
                'converted_leads' => Lead::selectRaw('COUNT(*)')
                    ->withoutGlobalScopes()
                    ->whereColumn('assigned_counsellor_id', 'users.id')
                    ->where('institution_id', $iid)
                    ->whereIn('status', ['fee_paid', 'enrolled'])
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNull('deleted_at'),
                // tasks completed in period
                'tasks_completed' => Task::selectRaw('COUNT(*)')
                    ->withoutGlobalScopes()
                    ->whereColumn('assigned_to', 'users.id')
                    ->where('institution_id', $iid)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$from, $to])
                    ->whereNull('deleted_at'),
                // currently overdue tasks (not period-scoped — point-in-time snapshot)
                'tasks_overdue' => Task::selectRaw('COUNT(*)')
                    ->withoutGlobalScopes()
                    ->whereColumn('assigned_to', 'users.id')
                    ->where('institution_id', $iid)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->where('due_at', '<', now())
                    ->whereNull('deleted_at'),
                // outbound calls in period
                'calls_made' => CallLog::selectRaw('COUNT(*)')
                    ->withoutGlobalScopes()
                    ->whereColumn('initiated_by', 'users.id')
                    ->where('institution_id', $iid)
                    ->whereBetween('called_at', [$from, $to])
                    ->whereNull('deleted_at'),
                // counselling sessions completed in period
                'sessions_completed' => CounsellingSession::selectRaw('COUNT(*)')
                    ->withoutGlobalScopes()
                    ->whereColumn('counsellor_id', 'users.id')
                    ->where('institution_id', $iid)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$from, $to])
                    ->whereNull('deleted_at'),
            ])
            ->with('campus:id,name')
            ->orderByDesc('new_leads')
            ->orderBy('users.name')
            ->get();
    }

    /**
     * AR-011 — Application Status Report: paginated application list with current pipeline stage.
     *
     * Scope rules:
     *  - counsellor  → own applications only (assigned_counsellor_id)
     *  - manager     → campus applications
     *  - director    → institution-wide
     *
     * Supported $filters keys:
     *  from, to (submitted_at range), status (ApplicationStatus value),
     *  programme_id (int), campus_id (int), counsellor_id (int)
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function applicationStatus(array $scope, array $filters): LengthAwarePaginator
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        return Application::withoutGlobalScopes()
            ->with([
                'lead:id,uuid,first_name,last_name,mobile,email',
                'programme:id,name',
                'assignedCounsellor:id,name',
                'campus:id,name',
            ])
            ->where('applications.institution_id', $scope['institution_id'])
            ->whereBetween('applications.submitted_at', [$from, $to])
            ->whereNull('applications.deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('applications.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('applications.assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($filters['status'] ?? null,       fn ($q, $v) => $q->where('applications.status', $v))
            ->when($filters['programme_id'] ?? null, fn ($q, $v) => $q->where('applications.programme_id', $v))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('applications.campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('applications.assigned_counsellor_id', $filters['counsellor_id'])
            )
            ->orderByDesc('applications.submitted_at')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * AR-011 export variant — returns up to 10 000 applications as a flat Collection (no pagination).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, Application>
     */
    public function applicationStatusForExport(array $scope, array $filters): Collection
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        return Application::withoutGlobalScopes()
            ->with([
                'lead:id,uuid,first_name,last_name,mobile,email',
                'programme:id,name',
                'assignedCounsellor:id,name',
                'campus:id,name',
            ])
            ->where('applications.institution_id', $scope['institution_id'])
            ->whereBetween('applications.submitted_at', [$from, $to])
            ->whereNull('applications.deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('applications.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('applications.assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($filters['status'] ?? null,       fn ($q, $v) => $q->where('applications.status', $v))
            ->when($filters['programme_id'] ?? null, fn ($q, $v) => $q->where('applications.programme_id', $v))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('applications.campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('applications.assigned_counsellor_id', $filters['counsellor_id'])
            )
            ->orderByDesc('applications.submitted_at')
            ->limit(10000)
            ->get();
    }

    /**
     * AR-013 — Lost Lead Analysis: paginated list of leads marked Lost in the selected period.
     *
     * Date anchor is status_changed_at (the timestamp when the lead was moved to Lost).
     * Scope rules: counsellor → own; manager → campus; director → institution-wide.
     *
     * Supported $filters keys:
     *  from, to (status_changed_at range), source, lost_reason, campus_id, counsellor_id
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function lostLeadAnalysis(array $scope, array $filters): LengthAwarePaginator
    {
        $from = ($filters['from'] ?? now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $to   = ($filters['to']   ?? now()->toDateString()) . ' 23:59:59';

        return Lead::withoutGlobalScopes()
            ->with([
                'assignedCounsellor:id,name',
                'campus:id,name',
                'programmeInterests' => fn ($q) => $q
                    ->wherePivot('is_primary', true)
                    ->select('crm_programmes.id', 'crm_programmes.name'),
            ])
            ->where('institution_id', $scope['institution_id'])
            ->where('status', 'lost')
            ->whereBetween('status_changed_at', [$from, $to])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($filters['source'] ?? null,      fn ($q, $v) => $q->where('source', $v))
            ->when($filters['lost_reason'] ?? null, fn ($q, $v) => $q->where('lost_reason', $v))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('assigned_counsellor_id', $filters['counsellor_id'])
            )
            ->orderByDesc('status_changed_at')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * AR-013 export variant — returns up to 10 000 lost leads as a flat Collection (no pagination).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, Lead>
     */
    public function lostLeadAnalysisForExport(array $scope, array $filters): Collection
    {
        $from = ($filters['from'] ?? now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $to   = ($filters['to']   ?? now()->toDateString()) . ' 23:59:59';

        return Lead::withoutGlobalScopes()
            ->with([
                'assignedCounsellor:id,name',
                'campus:id,name',
                'programmeInterests' => fn ($q) => $q
                    ->wherePivot('is_primary', true)
                    ->select('crm_programmes.id', 'crm_programmes.name'),
            ])
            ->where('institution_id', $scope['institution_id'])
            ->where('status', 'lost')
            ->whereBetween('status_changed_at', [$from, $to])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($filters['source'] ?? null,      fn ($q, $v) => $q->where('source', $v))
            ->when($filters['lost_reason'] ?? null, fn ($q, $v) => $q->where('lost_reason', $v))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('assigned_counsellor_id', $filters['counsellor_id'])
            )
            ->orderByDesc('status_changed_at')
            ->limit(10000)
            ->get();
    }

    /**
     * AR-013 — Aggregate lost leads grouped by lost_reason for the summary section.
     *
     * Respects all filters except lost_reason (so the breakdown shows all reasons
     * even when the detail table is filtered to one reason).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, object>
     */
    public function lostLeadsByReason(array $scope, array $filters): Collection
    {
        $from = ($filters['from'] ?? now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $to   = ($filters['to']   ?? now()->toDateString()) . ' 23:59:59';

        return DB::table('leads')
            ->selectRaw('lost_reason, COUNT(*) as total')
            ->where('institution_id', $scope['institution_id'])
            ->where('status', 'lost')
            ->whereBetween('status_changed_at', [$from, $to])
            ->whereNull('deleted_at')
            ->whereNotNull('lost_reason')
            ->when($scope['campus_id'],      fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('campus_id', $filters['campus_id'])
            )
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->groupBy('lost_reason')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * AR-014 — Fee Collection Report: paginated payment transactions with student, fee type, amount, status.
     *
     * Date anchor is attempted_at (always populated).
     * Scope rules: counsellor → own leads' transactions; manager → campus; director → institution-wide.
     *
     * Supported $filters keys:
     *  from, to (attempted_at range), status (PaymentStatus value), fee_type (FeeType value),
     *  campus_id (override), counsellor_id (override), programme_id
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function feeCollection(array $scope, array $filters): LengthAwarePaginator
    {
        $from = ($filters['from'] ?? now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $to   = ($filters['to']   ?? now()->toDateString()) . ' 23:59:59';

        return PaymentTransaction::withoutGlobalScopes()
            ->with([
                'lead:id,uuid,first_name,last_name,mobile,assigned_counsellor_id',
                'lead.assignedCounsellor:id,name',
                'application:id,uuid,lead_uuid,programme_id',
                'application.programme:id,name',
            ])
            ->where('payment_transactions.institution_id', $scope['institution_id'])
            ->whereBetween('payment_transactions.attempted_at', [$from, $to])
            ->whereNull('payment_transactions.deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('payment_transactions.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereHas('lead', fn ($lq) =>
                $lq->withoutGlobalScopes()->whereIn('assigned_counsellor_id', $scope['counsellor_ids'])
            ))
            ->when($filters['status'] ?? null,   fn ($q, $v) => $q->where('payment_transactions.status', $v))
            ->when($filters['fee_type'] ?? null, fn ($q, $v) => $q->where('payment_transactions.fee_type', $v))
            ->when($filters['programme_id'] ?? null, fn ($q, $v) =>
                $q->whereHas('application', fn ($aq) =>
                    $aq->withoutGlobalScopes()->where('programme_id', $v)
                )
            )
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('payment_transactions.campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->whereHas('lead', fn ($lq) =>
                    $lq->withoutGlobalScopes()->where('assigned_counsellor_id', $filters['counsellor_id'])
                )
            )
            ->orderByDesc('payment_transactions.attempted_at')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * AR-014 export variant — returns up to 10 000 payment transactions as a flat Collection (no pagination).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, PaymentTransaction>
     */
    public function feeCollectionForExport(array $scope, array $filters): Collection
    {
        $from = ($filters['from'] ?? now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $to   = ($filters['to']   ?? now()->toDateString()) . ' 23:59:59';

        return PaymentTransaction::withoutGlobalScopes()
            ->with([
                'lead:id,uuid,first_name,last_name,mobile,assigned_counsellor_id',
                'lead.assignedCounsellor:id,name',
                'application:id,uuid,lead_uuid,programme_id',
                'application.programme:id,name',
            ])
            ->where('payment_transactions.institution_id', $scope['institution_id'])
            ->whereBetween('payment_transactions.attempted_at', [$from, $to])
            ->whereNull('payment_transactions.deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('payment_transactions.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereHas('lead', fn ($lq) =>
                $lq->withoutGlobalScopes()->whereIn('assigned_counsellor_id', $scope['counsellor_ids'])
            ))
            ->when($filters['status'] ?? null,   fn ($q, $v) => $q->where('payment_transactions.status', $v))
            ->when($filters['fee_type'] ?? null, fn ($q, $v) => $q->where('payment_transactions.fee_type', $v))
            ->when($filters['programme_id'] ?? null, fn ($q, $v) =>
                $q->whereHas('application', fn ($aq) =>
                    $aq->withoutGlobalScopes()->where('programme_id', $v)
                )
            )
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('payment_transactions.campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->whereHas('lead', fn ($lq) =>
                    $lq->withoutGlobalScopes()->where('assigned_counsellor_id', $filters['counsellor_id'])
                )
            )
            ->orderByDesc('payment_transactions.attempted_at')
            ->limit(10000)
            ->get();
    }

    /**
     * AR-014 — Aggregate totals for the fee collection summary tiles.
     *
     * Returns: collected (SUM where success), pending_amount, refunded, total_transactions, successful_count.
     * Applies the same scope and filter constraints as feeCollection().
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function feeCollectionSummary(array $scope, array $filters): object
    {
        $from = ($filters['from'] ?? now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $to   = ($filters['to']   ?? now()->toDateString()) . ' 23:59:59';

        $q = DB::table('payment_transactions')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END), 0)                       AS collected,
                COALESCE(SUM(CASE WHEN status IN ('initiated','pending') THEN amount ELSE 0 END), 0)         AS pending_amount,
                COALESCE(SUM(CASE WHEN status IN ('refund_pending','refunded') THEN amount ELSE 0 END), 0)   AS refunded,
                COUNT(*)                                                                                     AS total_transactions,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END)                                         AS successful_count
            ")
            ->where('institution_id', $scope['institution_id'])
            ->whereBetween('attempted_at', [$from, $to])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($filters['status'] ?? null,   fn ($q, $v) => $q->where('status', $v))
            ->when($filters['fee_type'] ?? null, fn ($q, $v) => $q->where('fee_type', $v));

        // Counsellor scope via EXISTS to avoid row duplication from JOIN
        if ($scope['counsellor_ids']) {
            $ids = $scope['counsellor_ids'];
            $q->whereExists(fn ($s) => $s
                ->select(DB::raw(1))
                ->from('leads')
                ->whereColumn('leads.uuid', 'payment_transactions.lead_uuid')
                ->whereIn('leads.assigned_counsellor_id', $ids)
                ->whereNull('leads.deleted_at')
            );
        } elseif ($filters['counsellor_id'] ?? null) {
            $cid = $filters['counsellor_id'];
            $q->whereExists(fn ($s) => $s
                ->select(DB::raw(1))
                ->from('leads')
                ->whereColumn('leads.uuid', 'payment_transactions.lead_uuid')
                ->where('leads.assigned_counsellor_id', $cid)
                ->whereNull('leads.deleted_at')
            );
        }

        if (!$scope['campus_id'] && ($filters['campus_id'] ?? null)) {
            $q->where('campus_id', $filters['campus_id']);
        }

        if ($filters['programme_id'] ?? null) {
            $pid = $filters['programme_id'];
            $q->whereExists(fn ($s) => $s
                ->select(DB::raw(1))
                ->from('applications')
                ->whereColumn('applications.uuid', 'payment_transactions.application_uuid')
                ->where('applications.programme_id', $pid)
                ->whereNull('applications.deleted_at')
            );
        }

        return $q->first() ?? (object) [
            'collected'          => 0,
            'pending_amount'     => 0,
            'refunded'           => 0,
            'total_transactions' => 0,
            'successful_count'   => 0,
        ];
    }

    /**
     * AR-015 — Document Compliance Report: per-application document status breakdown.
     *
     * Each row is one application with sub-select counts for verified / pending / rejected / missing docs.
     * Date anchor is applications.submitted_at.
     * Scope rules: counsellor → own; manager → campus; director → institution-wide.
     *
     * Supported $filters keys:
     *  from, to (submitted_at range), compliance (compliant|pending|rejected),
     *  programme_id, campus_id, counsellor_id
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function documentCompliance(array $scope, array $filters): LengthAwarePaginator
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        $docBase = fn () => ApplicationDocument::withoutGlobalScopes()->whereNull('deleted_at');

        return Application::withoutGlobalScopes()
            ->select('applications.*')
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid'),
                'total_docs'
            )
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid')->where('status', 'verified'),
                'verified_docs'
            )
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid')->whereIn('status', ['submitted', 'under_review']),
                'pending_docs'
            )
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid')->where('status', 'rejected'),
                'rejected_docs'
            )
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid')->where('status', 'not_submitted'),
                'missing_docs'
            )
            ->with([
                'lead:id,uuid,first_name,last_name,mobile',
                'programme:id,name',
                'campus:id,name',
                'assignedCounsellor:id,name',
            ])
            ->where('applications.institution_id', $scope['institution_id'])
            ->whereBetween('applications.submitted_at', [$from, $to])
            ->whereNull('applications.deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('applications.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('applications.assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($filters['programme_id'] ?? null, fn ($q, $v) => $q->where('applications.programme_id', $v))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('applications.campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('applications.assigned_counsellor_id', $filters['counsellor_id'])
            )
            ->when($filters['compliance'] ?? null, function ($q, $compliance) {
                $docNonVerified = fn ($d) => $d->withoutGlobalScopes()
                    ->whereIn('status', ['not_submitted', 'submitted', 'under_review', 'rejected'])
                    ->whereNull('deleted_at');
                $docRejected = fn ($d) => $d->withoutGlobalScopes()
                    ->where('status', 'rejected')
                    ->whereNull('deleted_at');
                $docPending = fn ($d) => $d->withoutGlobalScopes()
                    ->whereIn('status', ['not_submitted', 'submitted', 'under_review'])
                    ->whereNull('deleted_at');

                match ($compliance) {
                    'compliant' => $q->whereHas('documents', fn ($d) => $d->withoutGlobalScopes()->whereNull('deleted_at'))
                                     ->whereDoesntHave('documents', $docNonVerified),
                    'pending'   => $q->whereHas('documents', $docPending)
                                     ->whereDoesntHave('documents', $docRejected),
                    'rejected'  => $q->whereHas('documents', $docRejected),
                    default     => null,
                };
            })
            ->orderByDesc('applications.submitted_at')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * AR-015 export variant — returns up to 10 000 applications with document counts (no pagination).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, Application>
     */
    public function documentComplianceForExport(array $scope, array $filters): Collection
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        $docBase = fn () => ApplicationDocument::withoutGlobalScopes()->whereNull('deleted_at');

        return Application::withoutGlobalScopes()
            ->select('applications.*')
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid'),
                'total_docs'
            )
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid')->where('status', 'verified'),
                'verified_docs'
            )
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid')->whereIn('status', ['submitted', 'under_review']),
                'pending_docs'
            )
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid')->where('status', 'rejected'),
                'rejected_docs'
            )
            ->selectSub(
                $docBase()->selectRaw('COUNT(*)')->whereColumn('application_uuid', 'applications.uuid')->where('status', 'not_submitted'),
                'missing_docs'
            )
            ->with([
                'lead:id,uuid,first_name,last_name,mobile',
                'programme:id,name',
                'campus:id,name',
                'assignedCounsellor:id,name',
            ])
            ->where('applications.institution_id', $scope['institution_id'])
            ->whereBetween('applications.submitted_at', [$from, $to])
            ->whereNull('applications.deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('applications.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('applications.assigned_counsellor_id', $scope['counsellor_ids']))
            ->when($filters['programme_id'] ?? null, fn ($q, $v) => $q->where('applications.programme_id', $v))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('applications.campus_id', $filters['campus_id'])
            )
            ->when(
                !$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null),
                fn ($q) => $q->where('applications.assigned_counsellor_id', $filters['counsellor_id'])
            )
            ->when($filters['compliance'] ?? null, function ($q, $compliance) {
                $docNonVerified = fn ($d) => $d->withoutGlobalScopes()
                    ->whereIn('status', ['not_submitted', 'submitted', 'under_review', 'rejected'])
                    ->whereNull('deleted_at');
                $docRejected = fn ($d) => $d->withoutGlobalScopes()
                    ->where('status', 'rejected')
                    ->whereNull('deleted_at');
                $docPending = fn ($d) => $d->withoutGlobalScopes()
                    ->whereIn('status', ['not_submitted', 'submitted', 'under_review'])
                    ->whereNull('deleted_at');

                match ($compliance) {
                    'compliant' => $q->whereHas('documents', fn ($d) => $d->withoutGlobalScopes()->whereNull('deleted_at'))
                                     ->whereDoesntHave('documents', $docNonVerified),
                    'pending'   => $q->whereHas('documents', $docPending)
                                     ->whereDoesntHave('documents', $docRejected),
                    'rejected'  => $q->whereHas('documents', $docRejected),
                    default     => null,
                };
            })
            ->orderByDesc('applications.submitted_at')
            ->limit(10000)
            ->get();
    }

    /**
     * AR-015 — Aggregate document counts for the summary tiles.
     *
     * Returns totals across all application_documents that belong to applications in the period.
     * Applies the same institution/campus/counsellor scope as documentCompliance().
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function documentComplianceSummary(array $scope, array $filters): object
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();
        $iid  = $scope['institution_id'];

        $appScope = Application::withoutGlobalScopes()
            ->select('applications.uuid')
            ->where('institution_id', $iid)
            ->whereBetween('submitted_at', [$from, $to])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']));

        if (!$scope['campus_id'] && ($filters['campus_id'] ?? null)) {
            $appScope->where('campus_id', $filters['campus_id']);
        }
        if (!$scope['counsellor_ids'] && ($filters['counsellor_id'] ?? null)) {
            $appScope->where('assigned_counsellor_id', $filters['counsellor_id']);
        }
        if ($filters['programme_id'] ?? null) {
            $appScope->where('programme_id', $filters['programme_id']);
        }

        $appUuids = $appScope->pluck('uuid');

        if ($appUuids->isEmpty()) {
            return (object) [
                'total_applications' => 0,
                'total_docs'         => 0,
                'verified_docs'      => 0,
                'pending_docs'       => 0,
                'rejected_docs'      => 0,
                'missing_docs'       => 0,
            ];
        }

        $stats = DB::table('application_documents')
            ->selectRaw("
                COUNT(*) as total_docs,
                SUM(CASE WHEN status = 'verified'                              THEN 1 ELSE 0 END) as verified_docs,
                SUM(CASE WHEN status IN ('submitted','under_review')            THEN 1 ELSE 0 END) as pending_docs,
                SUM(CASE WHEN status = 'rejected'                              THEN 1 ELSE 0 END) as rejected_docs,
                SUM(CASE WHEN status = 'not_submitted'                         THEN 1 ELSE 0 END) as missing_docs
            ")
            ->whereIn('application_uuid', $appUuids)
            ->whereNull('deleted_at')
            ->first();

        return (object) [
            'total_applications' => $appUuids->count(),
            'total_docs'         => (int) ($stats->total_docs   ?? 0),
            'verified_docs'      => (int) ($stats->verified_docs ?? 0),
            'pending_docs'       => (int) ($stats->pending_docs  ?? 0),
            'rejected_docs'      => (int) ($stats->rejected_docs ?? 0),
            'missing_docs'       => (int) ($stats->missing_docs  ?? 0),
        ];
    }

    /**
     * AR-016 — Year-on-Year Summary: institution-wide KPI comparison (current year vs previous year).
     *
     * Returns leads, applications, enrolments, and revenue totals for both years with delta and % change.
     * Year defaults to the current calendar year; `$filters['year']` overrides it.
     *
     * Scope rules: counsellor → own; manager → campus; director → institution-wide.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function yearOnYearSummary(array $scope, array $filters): object
    {
        $year     = (int) ($filters['year'] ?? now()->year);
        $prevYear = $year - 1;
        $iid      = $scope['institution_id'];

        // Hardcoded status groups — not user input, safe to interpolate
        $applied  = "'application_started','application_submitted','offer_issued','offer_accepted','fee_paid','enrolled'";
        $enrolled = "'fee_paid','enrolled'";

        $leadStats = DB::table('leads')
            ->selectRaw("
                SUM(CASE WHEN YEAR(created_at) = {$year}     THEN 1 ELSE 0 END)                                     AS current_leads,
                SUM(CASE WHEN YEAR(created_at) = {$prevYear}  THEN 1 ELSE 0 END)                                     AS prev_leads,
                SUM(CASE WHEN YEAR(created_at) = {$year}     AND status IN ({$applied})  THEN 1 ELSE 0 END)          AS current_applied,
                SUM(CASE WHEN YEAR(created_at) = {$prevYear}  AND status IN ({$applied})  THEN 1 ELSE 0 END)          AS prev_applied,
                SUM(CASE WHEN YEAR(created_at) = {$year}     AND status IN ({$enrolled}) THEN 1 ELSE 0 END)          AS current_enrolled,
                SUM(CASE WHEN YEAR(created_at) = {$prevYear}  AND status IN ({$enrolled}) THEN 1 ELSE 0 END)          AS prev_enrolled
            ")
            ->where('institution_id', $iid)
            ->whereRaw("YEAR(created_at) IN ({$year}, {$prevYear})")
            ->whereNull('deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->first();

        $revenueStats = DB::table('payment_transactions')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN YEAR(attempted_at) = {$year}     THEN amount ELSE 0 END), 0) AS current_revenue,
                COALESCE(SUM(CASE WHEN YEAR(attempted_at) = {$prevYear}  THEN amount ELSE 0 END), 0) AS prev_revenue
            ")
            ->where('institution_id', $iid)
            ->where('status', 'success')
            ->whereRaw("YEAR(attempted_at) IN ({$year}, {$prevYear})")
            ->whereNull('deleted_at')
            ->when($scope['campus_id'], fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->first();

        $delta = fn (int|float $cur, int|float $prev): array => [
            'current'  => $cur,
            'previous' => $prev,
            'delta'    => $cur - $prev,
            'pct'      => $prev > 0 ? round((($cur - $prev) / $prev) * 100, 1) : null,
        ];

        return (object) [
            'year'      => $year,
            'prev_year' => $prevYear,
            'leads'     => $delta((int) ($leadStats->current_leads    ?? 0), (int) ($leadStats->prev_leads    ?? 0)),
            'applied'   => $delta((int) ($leadStats->current_applied  ?? 0), (int) ($leadStats->prev_applied  ?? 0)),
            'enrolled'  => $delta((int) ($leadStats->current_enrolled ?? 0), (int) ($leadStats->prev_enrolled ?? 0)),
            'revenue'   => $delta((float) ($revenueStats->current_revenue ?? 0), (float) ($revenueStats->prev_revenue ?? 0)),
        ];
    }

    /**
     * AR-016 — Year-on-Year Breakdown: per-dimension rows (programme / source / campus).
     *
     * Each row carries: label, current_leads, prev_leads, current_applied, prev_applied,
     * current_enrolled, prev_enrolled, with deltas computed in Blade.
     *
     * Supported $filters keys: year (int), group_by (programme|source|campus), campus_id, counsellor_ids
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, object>
     */
    public function yearOnYearBreakdown(array $scope, array $filters): Collection
    {
        $year     = (int) ($filters['year'] ?? now()->year);
        $prevYear = $year - 1;
        $groupBy  = in_array($filters['group_by'] ?? '', ['source', 'campus'], true)
            ? $filters['group_by']
            : 'programme';
        $iid      = $scope['institution_id'];

        $applied  = "'application_started','application_submitted','offer_issued','offer_accepted','fee_paid','enrolled'";
        $enrolled = "'fee_paid','enrolled'";

        $baseSelect = "
            SUM(CASE WHEN YEAR(l.created_at) = {$year}     THEN 1 ELSE 0 END)                            AS current_leads,
            SUM(CASE WHEN YEAR(l.created_at) = {$prevYear}  THEN 1 ELSE 0 END)                            AS prev_leads,
            SUM(CASE WHEN YEAR(l.created_at) = {$year}     AND l.status IN ({$applied})  THEN 1 ELSE 0 END) AS current_applied,
            SUM(CASE WHEN YEAR(l.created_at) = {$prevYear}  AND l.status IN ({$applied})  THEN 1 ELSE 0 END) AS prev_applied,
            SUM(CASE WHEN YEAR(l.created_at) = {$year}     AND l.status IN ({$enrolled}) THEN 1 ELSE 0 END) AS current_enrolled,
            SUM(CASE WHEN YEAR(l.created_at) = {$prevYear}  AND l.status IN ({$enrolled}) THEN 1 ELSE 0 END) AS prev_enrolled
        ";

        $base = DB::table('leads as l')
            ->where('l.institution_id', $iid)
            ->whereRaw("YEAR(l.created_at) IN ({$year}, {$prevYear})")
            ->whereNull('l.deleted_at')
            ->when($scope['campus_id'],      fn ($q) => $q->where('l.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('l.assigned_counsellor_id', $scope['counsellor_ids']));

        return match ($groupBy) {
            'source' => (clone $base)
                ->selectRaw("l.source AS label, l.source AS label_key, {$baseSelect}")
                ->whereNotNull('l.source')
                ->groupBy('l.source')
                ->orderByDesc('current_leads')
                ->get(),

            'campus' => (clone $base)
                ->join('campuses as c', 'c.id', '=', 'l.campus_id')
                ->selectRaw("c.name AS label, c.name AS label_key, {$baseSelect}")
                ->groupByRaw('c.id, c.name')
                ->orderByDesc('current_leads')
                ->get(),

            default => (clone $base) // programme
                ->leftJoin('lead_programme_interests as lpi', fn ($j) => $j
                    ->on('lpi.lead_id', '=', 'l.id')
                    ->where('lpi.is_primary', '=', 1)
                )
                ->leftJoin('crm_programmes as p', 'p.id', '=', 'lpi.crm_programme_id')
                ->selectRaw("COALESCE(p.name, '(No Programme)') AS label, COALESCE(p.name, '(No Programme)') AS label_key, {$baseSelect}")
                ->groupByRaw('p.id, p.name')
                ->orderByDesc('current_leads')
                ->get(),
        };
    }

    /**
     * AR-017 — Agent Performance Report: per-agent summary of referred leads, funnel conversion, and commission.
     *
     * Date range anchors to lead.created_at for funnel counts and agent_commission_accruals.accrued_at for commission.
     * Agents are institution-wide (not campus-scoped), but campus_id narrows the lead counts when supplied.
     *
     * Supported $filters keys:
     *  from, to, agent_status (AgentStatus value), campus_id (limits lead counts to one campus)
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, Agent>
     */
    public function agentPerformance(array $scope, array $filters): Collection
    {
        $from = ($filters['from'] ?? now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $to   = ($filters['to']   ?? now()->toDateString()) . ' 23:59:59';
        $iid  = $scope['institution_id'];

        // Campus scope: honour locked scope first, then filter override
        $campusId = $scope['campus_id'] ?? ($filters['campus_id'] ?? null);

        $appliedStatuses  = ['application_started','application_submitted','offer_issued','offer_accepted','fee_paid','enrolled'];
        $enrolledStatuses = ['fee_paid','enrolled'];

        // Closure factories so each sub-select gets an independent query object
        $leadBase = fn () => Lead::withoutGlobalScopes()
            ->whereColumn('agent_id', 'agents.id')
            ->where('institution_id', $iid)
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('deleted_at')
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId));

        $accrualBase = fn () => AgentCommissionAccrual::withoutGlobalScopes()
            ->whereColumn('agent_id', 'agents.id')
            ->where('institution_id', $iid)
            ->whereBetween('accrued_at', [$from, $to]);

        return Agent::withoutGlobalScopes()
            ->where('agents.institution_id', $iid)
            ->whereNull('agents.deleted_at')
            ->when($filters['agent_status'] ?? null, fn ($q, $v) => $q->where('agents.status', $v))
            ->addSelect([
                'agents.id',
                'agents.uuid',
                'agents.name',
                'agents.email',
                'agents.status',
                'leads_referred' => $leadBase()->selectRaw('COUNT(*)'),
                'applied'        => $leadBase()->selectRaw('COUNT(*)')->whereIn('status', $appliedStatuses),
                'enrolled'       => $leadBase()->selectRaw('COUNT(*)')->whereIn('status', $enrolledStatuses),
                // All non-reversed accruals in period
                'commission_accrued' => $accrualBase()
                    ->selectRaw('COALESCE(SUM(commission_amount), 0)')
                    ->where('status', '!=', 'reversed'),
                // Only paid accruals in period
                'commission_paid' => $accrualBase()
                    ->selectRaw('COALESCE(SUM(commission_amount), 0)')
                    ->where('status', 'paid'),
            ])
            ->orderByDesc('leads_referred')
            ->orderBy('agents.name')
            ->get();
    }

    /**
     * AR-012 — Source Effectiveness Report: per-source funnel metrics for the period.
     *
     * Returns one row per distinct source with:
     *   total_leads, applied, offered, enrolled
     * Rates are computed in the view from these raw counts.
     *
     * Scope rules: counsellor → own leads only; manager → campus; director → institution-wide.
     *
     * Supported $filters keys: from, to, campus_id (override for director/manager)
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return Collection<int, object>
     */
    public function sourceEffectiveness(array $scope, array $filters): Collection
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        // Hardcoded status groupings — not user input, safe in selectRaw
        $applied  = "'application_started','application_submitted','offer_issued','offer_accepted','fee_paid','enrolled'";
        $offered  = "'offer_issued','offer_accepted','fee_paid','enrolled'";
        $enrolled = "'fee_paid','enrolled'";

        return DB::table('leads')
            ->selectRaw("
                source,
                COUNT(*) as total_leads,
                SUM(CASE WHEN status IN ({$applied})  THEN 1 ELSE 0 END) as applied,
                SUM(CASE WHEN status IN ({$offered})  THEN 1 ELSE 0 END) as offered,
                SUM(CASE WHEN status IN ({$enrolled}) THEN 1 ELSE 0 END) as enrolled
            ")
            ->where('institution_id', $scope['institution_id'])
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('deleted_at')
            ->whereNotNull('source')
            ->when($scope['campus_id'],      fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->when(
                !$scope['campus_id'] && ($filters['campus_id'] ?? null),
                fn ($q) => $q->where('campus_id', $filters['campus_id'])
            )
            ->groupBy('source')
            ->orderByDesc('total_leads')
            ->get();
    }
}
