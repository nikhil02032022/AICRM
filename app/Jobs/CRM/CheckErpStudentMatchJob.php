<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Enums\CRM\ErpMatchStatus;
use App\Events\CRM\ErpStudentMatchedEvent;
use App\Models\CRM\Lead;
use App\Services\CRM\Erp\ErpApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-020 — Async ERP Student Master lookup per lead
// Triggers on: lead creation, mobile/email update, manual "Check ERP" action
final class CheckErpStudentMatchJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Removed $queue property to avoid conflict with Queueable trait
    public int $tries = 1; // ErpApiClient handles its own internal retries (3×)
    public int $timeout = 30;

    public function __construct(
        public readonly string $leadUuid,
        public readonly int $institutionId,
    ) {}

    /** Prevents dispatching a duplicate job while one is already pending for this lead */
    public function uniqueId(): string
    {
        return "erp-match:{$this->leadUuid}";
    }

    public function handle(): void
    {
        // BRD: CRM-MT-001 — Bypass InstitutionScope + manually re-scope (job runs outside request)
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->where('institution_id', $this->institutionId)
            ->first();

        if ($lead === null || $lead->trashed()) {
            return;
        }

        // Mark as pending so the UI can show a spinner
        Lead::withoutGlobalScopes()
            ->where('id', $lead->id)
            ->update(['erp_match_status' => ErpMatchStatus::PENDING->value]);

        $client = ErpApiClient::forInstitution($this->institutionId);

        // DPDP: decrypt mobile only within the job; never passes mobile to logs
        $decryptedMobile = $lead->mobile;

        $erpStudent = $client->lookupStudentByMobile($decryptedMobile);

        if ($erpStudent !== null) {
            Lead::withoutGlobalScopes()
                ->where('id', $lead->id)
                ->update([
                    'erp_student_uuid' => $erpStudent->studentUuid,
                    'erp_match_status' => ErpMatchStatus::MATCHED->value,
                ]);

            // Reload so event handler gets fresh model
            $lead->refresh();
            ErpStudentMatchedEvent::dispatch($lead, $erpStudent);
            return;
        }

        // null means either 404 (no match) or API error — ErpApiClient distinguishes via logs
        // We default to NO_MATCH; ERROR is only set when we explicitly know the API failed.
        // Since ErpApiClient returns null for both cases, we check if base_url is configured.
        $status = config('services.a2a_erp.base_url', '') !== ''
            ? ErpMatchStatus::NO_MATCH
            : ErpMatchStatus::ERROR;

        Lead::withoutGlobalScopes()
            ->where('id', $lead->id)
            ->update(['erp_match_status' => $status->value]);
    }
}
