<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling;

use App\Enums\CRM\AssignmentMode;
use App\Events\CRM\LeadAssignedEvent;
use App\Models\CRM\CounsellorAssignmentConfig;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Repositories\CRM\Counselling\CounsellorAssignmentConfigRepositoryInterface;
use App\Repositories\CRM\Lead\LeadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// BRD: CRM-EC-006 — Auto-assignment (round-robin / load-balanced) and manual reassignment
// BRD: CRM-EC-007 — Admissions manager can manually reassign leads
final class CounsellorAssignmentService
{
    public function __construct(
        private readonly CounsellorAssignmentConfigRepositoryInterface $configRepository,
        private readonly LeadRepositoryInterface $leadRepository,
    ) {}

    /**
     * Auto-assign a lead to a counsellor per the institution's configured strategy.
     * Returns null if mode is MANUAL or no available counsellors exist.
     *
     * BRD: CRM-EC-006
     */
    public function autoAssign(Lead $lead): ?int
    {
        $config = $this->configRepository->getOrCreateForInstitution($lead->institution_id);

        if ($config->assignment_mode === AssignmentMode::MANUAL) {
            return null;
        }

        $counsellors = $this->getAvailableCounsellors($lead->institution_id, $config);

        if ($counsellors->isEmpty()) {
            return null;
        }

        $selected = match ($config->assignment_mode) {
            AssignmentMode::ROUND_ROBIN => $this->roundRobin($counsellors, $config),
            AssignmentMode::LOAD_BALANCED => $this->loadBalanced($counsellors),
            default => $counsellors->first(),
        };

        if ($selected === null) {
            return null;
        }

        $this->leadRepository->update($lead, ['assigned_counsellor_id' => $selected->id]);

        LeadAssignedEvent::dispatch(
            $lead->fresh(),
            $selected,
            null,
        );

        return $selected->id;
    }

    /**
     * Manually assign (or reassign) a lead to a specific counsellor.
     *
     * BRD: CRM-EC-007 — Admissions manager permission enforced via Policy before this call.
     */
    public function manualAssign(Lead $lead, int $counsellorId, int $performedByUserId): Lead
    {
        $previousCounsellorId = $lead->assigned_counsellor_id;

        $updated = $this->leadRepository->update($lead, ['assigned_counsellor_id' => $counsellorId]);

        $counsellor = User::find($counsellorId);
        $previousCounsellor = $previousCounsellorId ? User::find($previousCounsellorId) : null;

        LeadAssignedEvent::dispatch($updated, $counsellor, $previousCounsellor);

        // BRD: CRM-CR-002 — No PII in logs
        Log::info('Lead manually reassigned', [
            'lead_uuid' => $lead->uuid,
            'new_counsellor_id' => $counsellorId,
            'performed_by_id' => $performedByUserId,
        ]);

        return $updated;
    }

    /**
     * Return counsellors (Users with role = counsellor) who are under the lead cap.
     *
     * BRD: CRM-EC-006 — Workload cap enforced
     */
    public function getAvailableCounsellors(int $institutionId, ?CounsellorAssignmentConfig $config = null): Collection
    {
        $config ??= $this->configRepository->getOrCreateForInstitution($institutionId);

        // Subquery: count active leads per counsellor
        $activeCounts = DB::table('leads')
            ->select('assigned_counsellor_id', DB::raw('COUNT(*) as lead_count'))
            ->where('institution_id', $institutionId)
            ->whereNotIn('status', ['enrolled', 'lost'])
            ->whereNotNull('assigned_counsellor_id')
            ->whereNull('deleted_at')
            ->groupBy('assigned_counsellor_id');

        return User::where('institution_id', $institutionId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['senior-counsellor', 'junior-counsellor']))
            ->where('is_active', true)
            ->leftJoinSub($activeCounts, 'lead_counts', 'users.id', '=', 'lead_counts.assigned_counsellor_id')
            ->selectRaw('users.*, COALESCE(lead_counts.lead_count, 0) as active_lead_count')
            ->having('active_lead_count', '<', $config->max_leads_per_counsellor)
            ->orderBy('active_lead_count')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function roundRobin(Collection $counsellors, CounsellorAssignmentConfig $config): ?User
    {
        $pointerId = $config->round_robin_pointer_user_id;

        // Find the counsellor AFTER the current pointer in the sorted list
        $ids = $counsellors->pluck('id')->toArray();
        $cursor = array_search($pointerId, $ids, true);

        $nextIndex = ($cursor === false || $cursor >= count($ids) - 1) ? 0 : $cursor + 1;
        $selected = $counsellors[$nextIndex] ?? $counsellors->first();

        if ($selected) {
            $this->configRepository->advanceRoundRobinPointer($config, $selected->id);
        }

        return $selected;
    }

    private function loadBalanced(Collection $counsellors): ?User
    {
        // Already sorted by active_lead_count ASC from the query
        return $counsellors->first();
    }
}
