<?php

declare(strict_types=1);

namespace App\Services\CRM\Agent;

use App\Enums\CRM\AgentCommsChannel;
use App\Events\CRM\AgentBulkCommsSentEvent;
use App\Jobs\CRM\SendAgentBulkCommsJob;
use App\Models\CRM\AgentCommsLog;
use App\Repositories\CRM\Agent\AgentCommsRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-AG-008 — Agent bulk communications service — email/WhatsApp/SMS to agent network
final class AgentCommsService
{
    public function __construct(
        private readonly AgentCommsRepositoryInterface $repository
    ) {}

    /**
     * BRD: CRM-AG-008 — List all bulk comms logs for an institution (paginated)
     */
    public function list(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($institutionId, $perPage);
    }

    /**
     * BRD: CRM-AG-008 — Dispatch a bulk communication to the agent network
     * Filters out opted-out agents before dispatch (DPDP opt-out compliance)
     * Dispatches SendAgentBulkCommsJob for async delivery — never synchronous
     */
    public function send(
        int $institutionId,
        int $campusId,
        int $sentByUserId,
        AgentCommsChannel $channel,
        string $messageBody,
        array $recipientAgentIds,
        ?string $subject = null
    ): AgentCommsLog {
        // BRD: CRM-AG-008 — Recipient list is stored as IDs only — no PII (DPDP)
        $log = $this->repository->create([
            'uuid'                 => (string) Str::uuid(),
            'institution_id'       => $institutionId,
            'campus_id'            => $campusId,
            'sent_by'              => $sentByUserId,
            'channel'              => $channel,
            'subject'              => $subject,
            'message_body'         => $messageBody,
            'recipient_agent_ids'  => $recipientAgentIds,
            'recipient_count'      => count($recipientAgentIds),
            'status'               => 'queued',
            'opt_out_respected'    => true,
        ]);

        // BRD: CRM-AG-008 — Async send — never block the web request
        SendAgentBulkCommsJob::dispatch($log->id)->onQueue('crm-agents');

        return $log;
    }

    /**
     * BRD: CRM-AG-008 — Update delivery stats after job completes
     */
    public function recordDelivery(AgentCommsLog $log, int $delivered, int $failed): AgentCommsLog
    {
        $updated = $this->repository->update($log, [
            'status'          => 'sent',
            'delivered_count' => $delivered,
            'failed_count'    => $failed,
            'sent_at'         => now(),
        ]);

        AgentBulkCommsSentEvent::dispatch($updated);

        return $updated;
    }

    /**
     * BRD: CRM-AG-008 — Find by UUID
     */
    public function findByUuid(string $uuid): ?AgentCommsLog
    {
        return $this->repository->findByUuid($uuid);
    }
}
