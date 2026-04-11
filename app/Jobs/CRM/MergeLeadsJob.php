<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\DTOs\CRM\MergeLeadsDTO;
use App\Events\CRM\LeadsMergedEvent;
use App\Models\CRM\Activity;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Lead;
use App\Models\CRM\ScoreOverride;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-019 — Async lead merge worker
// Transfers all sub-records from secondary → primary, sets tombstone on secondary,
// soft-deletes secondary, and fires LeadsMergedEvent.
final class MergeLeadsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'crm-imports';
    public int $tries = 1; // Merge is not idempotently retryable — fail clearly
    public int $timeout = 60;

    public function __construct(
        public readonly MergeLeadsDTO $dto,
    ) {}

    /** Prevents a duplicate merge job being queued for the same pair */
    public function uniqueId(): string
    {
        return "merge:{$this->dto->primaryLeadUuid}:{$this->dto->secondaryLeadUuid}";
    }

    public function handle(): void
    {
        // BRD: CRM-MT-001 — Bypass InstitutionScope in job context; re-scope manually
        $primary = Lead::withoutGlobalScopes()
            ->where('uuid', $this->dto->primaryLeadUuid)
            ->where('institution_id', $this->dto->institutionId)
            ->first();

        $secondary = Lead::withoutGlobalScopes()
            ->where('uuid', $this->dto->secondaryLeadUuid)
            ->where('institution_id', $this->dto->institutionId)
            ->first();

        if ($primary === null || $secondary === null) {
            Log::warning('MergeLeadsJob: one or both leads not found.', [
                'primary' => $this->dto->primaryLeadUuid,
                'secondary' => $this->dto->secondaryLeadUuid,
            ]);
            return;
        }

        if ($primary->trashed() || $secondary->trashed()) {
            Log::warning('MergeLeadsJob: one or both leads are soft-deleted; aborting.', [
                'primary_uuid' => $primary->uuid,
                'secondary_uuid' => $secondary->uuid,
            ]);
            return;
        }

        if ($primary->isMerged() || $secondary->isMerged()) {
            Log::warning('MergeLeadsJob: one or both leads already have a merge tombstone; aborting.', [
                'primary_uuid' => $primary->uuid,
                'secondary_uuid' => $secondary->uuid,
            ]);
            return;
        }

        DB::transaction(function () use ($primary, $secondary): void {
            $this->transferActivities($primary, $secondary);
            $this->transferSessions($primary, $secondary);
            $this->transferScoreOverrides($primary, $secondary);
            $this->transferCommunicationLogs($primary, $secondary);
            $this->transferProgrammeInterests($primary, $secondary);
            $this->backFillProfileFields($primary, $secondary);
            $this->backFillErpLink($primary, $secondary);
            $this->stampTombstoneAndDelete($primary, $secondary);
            $this->clearDuplicateFlag($primary);
        });

        // Reload fresh after the transaction
        $primary->refresh();
        $secondary->refresh();

        LeadsMergedEvent::dispatch(
            $primary,
            $secondary,
            Activity::withoutGlobalScopes()->where('subject_type', Lead::class)->where('subject_id', $primary->id)->count(),
            CounsellingSession::withoutGlobalScopes()->where('lead_id', $primary->id)->count(),
            $this->dto->initiatedById,
        );
    }

    // -------------------------------------------------------------------------
    // Transfer helpers
    // -------------------------------------------------------------------------

    private function transferActivities(Lead $primary, Lead $secondary): void
    {
        // MorphMany pivot — update subject_id to point to primary
        Activity::withoutGlobalScopes()
            ->where('subject_type', Lead::class)
            ->where('subject_id', $secondary->id)
            ->update(['subject_id' => $primary->id]);
    }

    private function transferSessions(Lead $primary, Lead $secondary): void
    {
        CounsellingSession::withoutGlobalScopes()
            ->where('lead_id', $secondary->id)
            ->update(['lead_id' => $primary->id]);
    }

    private function transferScoreOverrides(Lead $primary, Lead $secondary): void
    {
        ScoreOverride::withoutGlobalScopes()
            ->where('lead_id', $secondary->id)
            ->update(['lead_id' => $primary->id]);
    }

    private function transferCommunicationLogs(Lead $primary, Lead $secondary): void
    {
        CommunicationLog::withoutGlobalScopes()
            ->where('lead_id', $secondary->id)
            ->update(['lead_id' => $primary->id]);
    }

    private function transferProgrammeInterests(Lead $primary, Lead $secondary): void
    {
        // Get secondary's programme IDs that primary doesn't already have
        $primaryProgrammeIds = DB::table('lead_programme_interests')
            ->where('lead_id', $primary->id)
            ->pluck('crm_programme_id')
            ->toArray();

        $secondaryInterests = DB::table('lead_programme_interests')
            ->where('lead_id', $secondary->id)
            ->get();

        foreach ($secondaryInterests as $interest) {
            if (!in_array($interest->crm_programme_id, $primaryProgrammeIds, true)) {
                DB::table('lead_programme_interests')->insert([
                    'lead_id' => $primary->id,
                    'crm_programme_id' => $interest->crm_programme_id,
                    'is_primary' => $interest->is_primary,
                    'created_at' => $interest->created_at,
                    'updated_at' => now(),
                ]);
            }
        }

        // Remove secondary's interests (now transferred or duplicates)
        DB::table('lead_programme_interests')->where('lead_id', $secondary->id)->delete();
    }

    /**
     * Back-fill null profile fields on primary from secondary.
     * Primary always wins on non-null fields (identity, PII, status, consent).
     */
    private function backFillProfileFields(Lead $primary, Lead $secondary): void
    {
        $backFillFields = [
            'qualification', 'marks_10th', 'board_10th',
            'marks_12th', 'board_12th', 'graduation_percentage',
            'graduation_university', 'preferred_intake', 'date_of_birth',
            'city', 'state', 'nationality', 'notes',
        ];

        $updates = [];
        foreach ($backFillFields as $field) {
            if ($primary->{$field} === null && $secondary->{$field} !== null) {
                $updates[$field] = $secondary->{$field};
            }
        }

        if (!empty($updates)) {
            Lead::withoutGlobalScopes()
                ->where('id', $primary->id)
                ->update($updates);
        }
    }

    /**
     * If primary has no ERP link but secondary has one, copy it across.
     */
    private function backFillErpLink(Lead $primary, Lead $secondary): void
    {
        if ($primary->erp_student_uuid === null && $secondary->erp_student_uuid !== null) {
            Lead::withoutGlobalScopes()
                ->where('id', $primary->id)
                ->update([
                    'erp_student_uuid' => $secondary->erp_student_uuid,
                    'erp_match_status' => $secondary->erp_match_status?->value,
                ]);
        }
    }

    /** Set merge tombstone on secondary and soft-delete it */
    private function stampTombstoneAndDelete(Lead $primary, Lead $secondary): void
    {
        Lead::withoutGlobalScopes()
            ->where('id', $secondary->id)
            ->update([
                'merged_into_uuid' => $primary->uuid,
                'merged_at' => now(),
                'merge_initiated_by' => $this->dto->initiatedById,
                'deleted_at' => now(),
            ]);
    }

    /** Clear the duplicate suspected flag on primary after a successful merge */
    private function clearDuplicateFlag(Lead $primary): void
    {
        Lead::withoutGlobalScopes()
            ->where('id', $primary->id)
            ->update([
                'is_duplicate_suspected' => false,
                'duplicate_of_uuid' => null,
            ]);
    }
}
