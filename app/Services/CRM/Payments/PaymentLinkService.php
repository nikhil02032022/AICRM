<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Enums\CRM\Payments\PaymentChannel;
use App\Models\CRM\Payments\PaymentLink;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Notifications\CRM\Payments\PaymentLinkNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

// BRD: CRM-FM-004 — Generate signed payment links and dispatch via channel.
final class PaymentLinkService
{
    public function generate(
        PaymentTransaction $transaction,
        PaymentChannel $channel,
        string $recipient,
    ): PaymentLink {
        $token = Str::random(40);
        $ttl   = (int) config('crm_payments.link.ttl_minutes', 4320);

        $link = new PaymentLink([
            'institution_id'         => $transaction->institution_id,
            'payment_transaction_id' => $transaction->id,
            'token'                  => $token,
            'channel'                => $channel,
            'recipient'              => $recipient,
            'shared_at'              => now(),
            'expires_at'             => now()->addMinutes($ttl),
            'created_by'             => Auth::id(),
        ]);
        $link->save();

        $url = route(config('crm_payments.link.route_name'), ['token' => $token]);

        // Channel routing: email via Notification, sms/whatsapp via on-demand notification.
        Notification::route($channel === PaymentChannel::EMAIL ? 'mail' : $channel->value, $recipient)
            ->notify(new PaymentLinkNotification($link, $url));

        return $link;
    }
}
