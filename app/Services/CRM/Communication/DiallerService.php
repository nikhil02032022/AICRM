<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\DiallerLogStatus;
use App\Enums\CRM\DiallerSessionStatus;
use App\Events\CRM\Communication\CallPlacedEvent;
use App\Jobs\CRM\Communication\DiallerJob;
use App\Models\CRM\DiallerLog;
use App\Models\CRM\DiallerSession;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

// BRD: CRM-TC-001 — Power/auto-dialler orchestration for campaign lead queues
final class DiallerService
{
    public function __construct(
        private readonly VoiceService $voiceService,
    ) {}

    /**
     * BRD: CRM-TC-001 — Start dialler session for explicit lead list or default counsellor queue.
     *
     * @param list<string> $leadUuids
     */
    public function startSession(User $user, array $leadUuids, ?string $campaignName, int $leadLimit = 25, ?int $telecallingCampaignId = null): DiallerSession
    {
        $leads = $this->resolveQueueLeads($user, $leadUuids, $leadLimit);

        $session = DiallerSession::create([
            'institution_id' => $user->institution_id,
            'telecalling_campaign_id' => $telecallingCampaignId,
            'campaign_name' => $campaignName,
            'started_by' => $user->id,
            'status' => DiallerSessionStatus::QUEUED,
            'total_leads' => $leads->count(),
            'queued_calls' => $leads->count(),
        ]);

        $order = 1;
        foreach ($leads as $lead) {
            DiallerLog::create([
                'institution_id' => $session->institution_id,
                'campus_id' => $lead->campus_id,
                'dialler_session_id' => $session->id,
                'lead_id' => $lead->id,
                'queue_order' => $order++,
                'status' => DiallerLogStatus::QUEUED,
            ]);
        }

        if ($session->queued_calls > 0) {
            DiallerJob::dispatch($session->uuid)->onQueue('crm-telecalling');
        }

        return $session->fresh();
    }

    public function stopSession(DiallerSession $session): DiallerSession
    {
        if (in_array($session->status, [DiallerSessionStatus::COMPLETED, DiallerSessionStatus::STOPPED], true)) {
            return $session;
        }

        $session->update([
            'status' => DiallerSessionStatus::STOPPED,
            'ended_at' => now(),
        ]);

        return $session->fresh();
    }

    public function completeSession(DiallerSession $session): DiallerSession
    {
        if ($session->status === DiallerSessionStatus::COMPLETED) {
            return $session;
        }

        $session->update([
            'status' => DiallerSessionStatus::COMPLETED,
            'queued_calls' => 0,
            'ended_at' => now(),
        ]);

        return $session->fresh();
    }

    public function queueNext(DiallerSession $session): void
    {
        if (in_array($session->status, [DiallerSessionStatus::STOPPED, DiallerSessionStatus::COMPLETED], true)) {
            return;
        }

        DiallerJob::dispatch($session->uuid)->onQueue('crm-telecalling');
    }

    /**
     * BRD: CRM-TC-001 — Place exactly one queued call; next call is queued after call completion.
     */
    public function processNextCall(string $sessionUuid): void
    {
        $session = DiallerSession::withoutGlobalScopes()
            ->where('uuid', $sessionUuid)
            ->first();

        if ($session === null) {
            return;
        }

        if (in_array($session->status, [DiallerSessionStatus::STOPPED, DiallerSessionStatus::COMPLETED], true)) {
            return;
        }

        if ($session->started_at === null || $session->status === DiallerSessionStatus::QUEUED) {
            $session->update([
                'status' => DiallerSessionStatus::ACTIVE,
                'started_at' => $session->started_at ?? now(),
            ]);
        }

        $next = DiallerLog::withoutGlobalScopes()
            ->where('dialler_session_id', $session->id)
            ->where('status', DiallerLogStatus::QUEUED)
            ->orderBy('queue_order')
            ->first();

        if ($next === null) {
            $this->completeSession($session);

            return;
        }

        $lead = Lead::withoutGlobalScopes()->find($next->lead_id);

        if ($lead === null || $lead->dnc_at !== null || $lead->opt_out || ! $lead->call_consent_given) {
            $this->markSkipped($session, $next, 'lead_not_callable');
            $this->queueNextIfPending($session);

            return;
        }

        if (empty($lead->mobile)) {
            $this->markSkipped($session, $next, 'mobile_missing');
            $this->queueNextIfPending($session);

            return;
        }

        $agent = User::withoutGlobalScopes()->find($session->started_by);

        if ($agent === null) {
            $this->markFailed($session, $next, 'agent_missing');
            return;
        }

        try {
            $callLog = $this->voiceService->initiateClickToCall($lead, $agent);

            $next->update([
                'status' => DiallerLogStatus::PLACED,
                'call_log_id' => $callLog->id,
                'attempted_at' => now(),
                'placed_at' => now(),
                'failure_reason' => null,
            ]);

            $session->increment('placed_calls');
            $session->decrement('queued_calls');
            $session->update(['last_dialled_at' => now()]);

            event(new CallPlacedEvent($lead, $callLog, $session->fresh() ?? $session, $next->fresh() ?? $next));
        } catch (\Throwable $e) {
            Log::warning('Dialler call placement failed', [
                'dialler_session_uuid' => $session->uuid,
                'dialler_log_uuid' => $next->uuid,
                'lead_uuid' => $lead->uuid,
                'reason' => $e->getMessage(),
            ]);

            $this->markFailed($session, $next, 'provider_error');
            $this->queueNextIfPending($session);
        }
    }

    /**
     * @param list<string> $leadUuids
     * @return Collection<int, Lead>
     */
    private function resolveQueueLeads(User $user, array $leadUuids, int $leadLimit): Collection
    {
        $query = Lead::query()
            ->where('institution_id', $user->institution_id)
            ->whereNull('dnc_at')
            ->where('opt_out', false)
            ->where('call_consent_given', true)
            ->whereNotNull('mobile');

        if ($leadUuids !== []) {
            return $query
                ->whereIn('uuid', $leadUuids)
                ->orderByDesc('lead_score')
                ->limit($leadLimit)
                ->get();
        }

        return $query
            ->where('assigned_counsellor_id', $user->id)
            ->orderByDesc('lead_score')
            ->orderBy('created_at')
            ->limit($leadLimit)
            ->get();
    }

    private function markSkipped(DiallerSession $session, DiallerLog $log, string $reason): void
    {
        $log->update([
            'status' => DiallerLogStatus::SKIPPED,
            'failure_reason' => $reason,
            'attempted_at' => now(),
        ]);

        $session->decrement('queued_calls');
        $session->increment('skipped_calls');
    }

    private function markFailed(DiallerSession $session, DiallerLog $log, string $reason): void
    {
        $log->update([
            'status' => DiallerLogStatus::FAILED,
            'failure_reason' => $reason,
            'attempted_at' => now(),
        ]);

        $session->decrement('queued_calls');
        $session->increment('failed_calls');
    }

    private function queueNextIfPending(DiallerSession $session): void
    {
        $fresh = $session->fresh();
        if ($fresh === null) {
            return;
        }

        if ($fresh->queued_calls > 0 && $fresh->status === DiallerSessionStatus::ACTIVE) {
            $this->queueNext($fresh);

            return;
        }

        if ($fresh->queued_calls <= 0 && $fresh->status === DiallerSessionStatus::ACTIVE) {
            $this->completeSession($fresh);
        }
    }
}
