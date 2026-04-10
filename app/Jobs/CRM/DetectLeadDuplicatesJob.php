<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\DuplicateLeadFlaggedEvent;
use App\Models\CRM\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-018 — Auto-detect duplicate leads on mobile/email match AND name+course
// combination, persist the flag to the DB, and fire DuplicateLeadFlaggedEvent.
final class DetectLeadDuplicatesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public function __construct(
        public readonly string $leadUuid,
        public readonly int $institutionId,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        // BRD: CRM-LC-018 — Deduplicate job dispatches per lead per run cycle
        return "dedup:{$this->leadUuid}";
    }

    public function handle(): void
    {
        // BRD: CRM-CR-002 — bypass InstitutionScope then re-scope manually (no PII in query filter)
        $lead = Lead::withoutGlobalScopes()
            ->with('programmeInterests:id')
            ->where('uuid', $this->leadUuid)
            ->where('institution_id', $this->institutionId)
            ->whereNull('deleted_at')
            ->first();

        if ($lead === null) {
            return;
        }

        // ── Step 1: mobile / email match (PHP-level because columns are encrypted at rest) ──
        $candidates = Lead::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->where('uuid', '!=', $this->leadUuid)
            ->whereNull('deleted_at')
            ->get(['id', 'uuid', 'mobile', 'email', 'first_name', 'last_name']);

        /** @var Collection<int, Lead> $mobileEmailMatches */
        $mobileEmailMatches = $candidates->filter(
            fn (Lead $l) => $l->mobile === $lead->mobile
                || ($lead->email !== null && $lead->email !== '' && $l->email === $lead->email)
        )->values();

        // ── Step 2: name + course combination match (BRD: "name+course combination") ──
        $leadProgrammeIds = $lead->programmeInterests->pluck('id');

        /** @var Collection<int, Lead> $nameCourseMatches */
        $nameCourseMatches = new Collection;

        if ($leadProgrammeIds->isNotEmpty()) {
            $nameMatchCandidates = Lead::withoutGlobalScopes()
                ->with('programmeInterests:id')
                ->where('institution_id', $this->institutionId)
                ->where('uuid', '!=', $this->leadUuid)
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(first_name) = ?', [mb_strtolower($lead->first_name)])
                ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($lead->last_name)])
                ->get(['id', 'uuid', 'first_name', 'last_name']);

            $nameCourseMatches = $nameMatchCandidates->filter(
                fn (Lead $l) => $l->programmeInterests->pluck('id')
                    ->intersect($leadProgrammeIds)
                    ->isNotEmpty()
            )->values();
        }

        // ── Merge unique matches ──
        /** @var Collection<int, Lead> $allDuplicates */
        $allDuplicates = $mobileEmailMatches->merge($nameCourseMatches)
            ->unique('uuid')
            ->values();

        if ($allDuplicates->isEmpty()) {
            return;
        }

        // BRD: CRM-LC-018 — Determine the match type for the event payload
        $matchType = match (true) {
            $mobileEmailMatches->isNotEmpty() && $nameCourseMatches->isNotEmpty() => 'both',
            $mobileEmailMatches->isNotEmpty() => 'mobile_email',
            default => 'name_course',
        };

        // BRD: CRM-LC-018 — Persist the flag to the leads table (non-blocking raw update,
        // bypasses global scope and observer to avoid double-audit-log entry)
        Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->update([
                'is_duplicate_suspected' => true,
                'duplicate_of_uuid' => $allDuplicates->first()->uuid,
            ]);

        // BRD: CRM-CR-002 — Never log PII; only UUIDs and counts
        Log::warning('Duplicate lead flagged', [
            'lead_uuid' => $this->leadUuid,
            'duplicate_count' => $allDuplicates->count(),
            'match_type' => $matchType,
        ]);

        // Refresh lead model after raw update so event carries current state
        $lead->is_duplicate_suspected = true;
        $lead->duplicate_of_uuid = $allDuplicates->first()->uuid;

        DuplicateLeadFlaggedEvent::dispatch($lead, $allDuplicates, $matchType);
    }
}
