<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\BulkImportCompletedEvent;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// BRD: CRM-LC-012 — Notify the user who initiated the import when the batch completes
final class NotifyImportCompleted implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(BulkImportCompletedEvent $event): void
    {
        $batch = $event->batch;

        if ($batch->initiated_by_user_id === null) {
            return; // Webhook-triggered batch — no user to notify
        }

        $user = User::find($batch->initiated_by_user_id);

        if ($user === null) {
            return;
        }

        $successful = $batch->processed_rows - $batch->failed_rows;
        $subject    = $event->partialFailure
            ? "Import completed with {$batch->failed_rows} errors — {$batch->file_name}"
            : "Import completed successfully — {$batch->file_name}";

        // BRD: CRM-CR-002 — No PII in log messages
        Log::info('NotifyImportCompleted: sending email notification', [
            'batch_uuid' => $batch->uuid,
            'user_id'    => $user->id,
        ]);

        // Send a plain notification email (uses default mail driver from config/mail.php)
        Mail::to($user->email)->send(
            new \App\Mail\CRM\ImportCompletedMail(
                batch:          $batch,
                successful:     $successful,
                failed:         $batch->failed_rows,
                hasErrorReport: $batch->error_report_path !== null,
                subject:        $subject,
            )
        );
    }
}
