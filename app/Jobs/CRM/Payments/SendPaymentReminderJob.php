<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Payments;

use App\Enums\CRM\Payments\PaymentChannel;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Enums\CRM\Payments\ReminderStatus;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\PaymentReminder;
use App\Notifications\CRM\Payments\PaymentLinkNotification;
use App\Services\CRM\Payments\PaymentLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

// BRD: CRM-FM-010 — Dispatch one queued reminder per row.
class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $reminderId) {}

    public function handle(PaymentLinkService $linkService): void
    {
        $reminder = PaymentReminder::withoutGlobalScopes()->find($this->reminderId);
        if ($reminder === null || $reminder->status !== ReminderStatus::PENDING) {
            return;
        }

        $txn = $reminder->transaction()->withoutGlobalScopes()->first();
        if ($txn === null || ! $txn->status->isOpen()) {
            $reminder->status = ReminderStatus::SKIPPED;
            $reminder->save();
            return;
        }

        if ($reminder->opted_out) {
            $reminder->status = ReminderStatus::SKIPPED;
            $reminder->save();
            return;
        }

        $lead = Lead::withoutGlobalScopes()->where('uuid', $txn->lead_uuid)->first();
        $recipient = match ($reminder->channel) {
            PaymentChannel::EMAIL => $lead?->email,
            PaymentChannel::SMS, PaymentChannel::WHATSAPP => $lead?->phone,
            default => null,
        };

        if ($recipient === null) {
            $reminder->status = ReminderStatus::SKIPPED;
            $reminder->failure_reason = 'no_recipient';
            $reminder->save();
            return;
        }

        try {
            $link = $txn->links()->latest()->first()
                ?? $linkService->generate($txn, $reminder->channel, $recipient);

            $url = route(config('crm_payments.link.route_name'), ['token' => $link->token]);

            Notification::route(
                $reminder->channel === PaymentChannel::EMAIL ? 'mail' : $reminder->channel->value,
                $recipient,
            )->notify(new PaymentLinkNotification($link, $url));

            $reminder->status = ReminderStatus::SENT;
            $reminder->sent_at = now();
            $reminder->save();
        } catch (Throwable $e) {
            $reminder->status = ReminderStatus::FAILED;
            $reminder->failure_reason = mb_substr($e->getMessage(), 0, 250);
            $reminder->save();
            throw $e;
        }
    }
}
