<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\DiallerSessionStatus;
use App\Events\CRM\Communication\CallLoggedEvent;
use App\Models\CRM\DiallerLog;
use App\Models\CRM\DiallerSession;
use App\Services\CRM\Communication\DiallerService;

// BRD: CRM-TC-001 — Advance auto-dialler queue when the current call finishes.
final class ContinueDiallerOnCallLogged
{
    public function __construct(
        private readonly DiallerService $diallerService,
    ) {}

    public function handle(CallLoggedEvent $event): void
    {
        $diallerLog = DiallerLog::withoutGlobalScopes()
            ->where('call_log_id', $event->callLog->id)
            ->first();

        if ($diallerLog === null) {
            return;
        }

        $session = DiallerSession::withoutGlobalScopes()->find($diallerLog->dialler_session_id);

        if ($session === null) {
            return;
        }

        if (in_array($session->status, [DiallerSessionStatus::STOPPED, DiallerSessionStatus::COMPLETED], true)) {
            return;
        }

        if ($session->queued_calls > 0) {
            $this->diallerService->queueNext($session);

            return;
        }

        $this->diallerService->completeSession($session);
    }
}
