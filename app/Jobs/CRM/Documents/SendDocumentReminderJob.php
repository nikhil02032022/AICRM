<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Documents;

use App\Enums\CRM\Documents\DocumentReminderStatus;
use App\Enums\CRM\Documents\DocumentStatus;
use App\Enums\CRM\Payments\PaymentChannel;
use App\Models\CRM\Documents\DocumentReminder;
use App\Models\CRM\Lead;
use App\Notifications\CRM\Documents\DocumentReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

// BRD: CRM-DM-005
class SendDocumentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $reminderId) {}

    public function handle(): void
    {
        $reminder = DocumentReminder::withoutGlobalScopes()->find($this->reminderId);
        if ($reminder === null || $reminder->status !== DocumentReminderStatus::PENDING) {
            return;
        }
        if ($reminder->opted_out) {
            $reminder->status = DocumentReminderStatus::SKIPPED;
            $reminder->save();
            return;
        }

        $doc = $reminder->document()->withoutGlobalScopes()->first();
        if ($doc === null || ! $doc->status->isPending() || $doc->status === DocumentStatus::VERIFIED) {
            $reminder->status = DocumentReminderStatus::SKIPPED;
            $reminder->save();
            return;
        }

        $lead = Lead::withoutGlobalScopes()->where('uuid', $doc->lead_uuid)->first();
        $recipient = match ($reminder->channel) {
            PaymentChannel::EMAIL => $lead?->email,
            PaymentChannel::SMS, PaymentChannel::WHATSAPP => $lead?->phone,
            default => null,
        };

        if ($recipient === null) {
            $reminder->status = DocumentReminderStatus::SKIPPED;
            $reminder->failure_reason = 'no_recipient';
            $reminder->save();
            return;
        }

        try {
            Notification::route(
                $reminder->channel === PaymentChannel::EMAIL ? 'mail' : $reminder->channel->value,
                $recipient,
            )->notify(new DocumentReminderNotification($doc, $reminder->channel));

            $reminder->status = DocumentReminderStatus::SENT;
            $reminder->sent_at = now();
            $reminder->save();
        } catch (Throwable $e) {
            $reminder->status = DocumentReminderStatus::FAILED;
            $reminder->failure_reason = mb_substr($e->getMessage(), 0, 250);
            $reminder->save();
            throw $e;
        }
    }
}
