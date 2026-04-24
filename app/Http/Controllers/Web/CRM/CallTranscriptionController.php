<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\AI\TranscriptionStatus;
use App\Http\Controllers\Controller;
use App\Jobs\CRM\AI\TranscribeCallJob;
use App\Models\CRM\CallLog;
use Illuminate\Http\RedirectResponse;

// BRD: CRM-AI-007 — Retry endpoint for failed AI call transcriptions
final class CallTranscriptionController extends Controller
{
    // BRD: CRM-AI-007 — Re-dispatch TranscribeCallJob for failed transcriptions (counsellor or manager only)
    public function retry(CallLog $callLog): RedirectResponse
    {
        $this->authorize('retry', $callLog);

        abort_if(
            $callLog->transcription_status !== TranscriptionStatus::Failed,
            403,
            'Only failed transcriptions can be retried.',
        );

        $callLog->update(['transcription_status' => TranscriptionStatus::Pending]);

        TranscribeCallJob::dispatch($callLog->uuid);

        return back()->with('success', 'Transcription retry queued. The summary will update shortly.');
    }
}
