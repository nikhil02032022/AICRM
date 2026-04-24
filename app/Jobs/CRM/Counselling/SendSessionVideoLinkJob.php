<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Counselling;

use App\Models\CRM\CounsellingSession;
use App\Notifications\CRM\Counselling\SessionVideoLinkNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-EC-018 — Queued delivery of meeting link to lead via email and WhatsApp
final class SendSessionVideoLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly string $sessionId,
    ) {}

    public function handle(): void
    {
        $session = CounsellingSession::withoutGlobalScopes()
            ->with(['lead', 'counsellor'])
            ->find($this->sessionId);

        if ($session === null) {
            Log::warning('SendSessionVideoLinkJob: session not found', ['session_id' => $this->sessionId]);
            return;
        }

        if (empty($session->meeting_link)) {
            Log::warning('SendSessionVideoLinkJob: no meeting link on session', ['session_id' => $this->sessionId]);
            return;
        }

        $lead = $session->lead;

        if ($lead === null) {
            return;
        }

        $lead->notify(new SessionVideoLinkNotification($session));
    }
}
