<?php

declare(strict_types=1);

namespace App\Jobs\CRM\AI;

use App\Enums\CRM\AI\TranscriptionStatus;
use App\Models\CRM\CallLog;
use App\Services\CRM\AI\CallTranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-007 — Async Claude API call transcription; deduped per call_log via Redis lock
final class TranscribeCallJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly string $callLogUuid,
    ) {
        $this->onQueue('ai')->delay(now()->addSeconds(30));
    }

    public function uniqueId(): string
    {
        return "call-transcription:{$this->callLogUuid}";
    }

    public function handle(CallTranscriptionService $service): void
    {
        $callLog = CallLog::withoutGlobalScopes()
            ->where('uuid', $this->callLogUuid)
            ->first();

        if ($callLog === null) {
            return;
        }

        // Idempotency: skip if already successfully completed
        if ($callLog->transcription_status === TranscriptionStatus::Completed) {
            return;
        }

        // Institution-scoped Redis lock prevents concurrent duplicate transcriptions
        $lockKey = "transcription-lock:{$callLog->institution_id}:{$callLog->id}";
        $lock    = Cache::lock($lockKey, 120);

        if (! $lock->get()) {
            Log::info('TranscribeCallJob: skipped — lock held', ['call_log_uuid' => $this->callLogUuid]);

            return;
        }

        try {
            $callLog->update(['transcription_status' => TranscriptionStatus::Processing]);

            $summary = $service->transcribe($callLog);

            // Auto-populate disposition_notes with summary_sentence if counsellor left it blank
            $callLog->refresh();
            if (empty($callLog->disposition_notes) && ! empty($summary['summary_sentence'])) {
                $callLog->update(['disposition_notes' => $summary['summary_sentence']]);
            }

            Log::info('TranscribeCallJob: transcription completed', [
                'call_log_uuid'    => $this->callLogUuid,
                'lead_temperature' => $summary['lead_temperature'] ?? null,
            ]);
        } finally {
            $lock->release();
        }
    }

    public function failed(\Throwable $e): void
    {
        CallLog::withoutGlobalScopes()
            ->where('uuid', $this->callLogUuid)
            ->update(['transcription_status' => TranscriptionStatus::Failed]);

        Log::error('TranscribeCallJob: failed after all retries', [
            'call_log_uuid' => $this->callLogUuid,
            'error'         => $e->getMessage(),
        ]);
    }
}
