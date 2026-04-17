<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\OfferLetter;
use App\Services\CRM\Communication\CommunicationEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use function activity;
use Throwable;

// BRD: CRM-AP-013 — Async offer letter delivery via email/WhatsApp
final class SendOfferLetterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly OfferLetter $offerLetter,
        public readonly string $channel = 'email',
    ) {}

    public function handle(CommunicationEngineService $comms): void
    {
        DB::transaction(function () use ($comms) {
            $offer = $this->offerLetter->fresh();
            if ($offer->status !== 'generated') {
                Log::warning('OfferLetter not generated, cannot send', ['offer_uuid' => $offer->uuid]);
                return;
            }
            try {
                $result = $comms->sendOfferLetter(
                    offerLetter: $offer,
                    channel: $this->channel,
                );
                $offer->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'sent_via' => $this->channel,
                    'delivery_status' => $result['status'] ?? 'pending',
                    'delivery_message_id' => $result['message_id'] ?? null,
                ]);
                activity()
                    ->performedOn($offer)
                    ->withProperties([
                        'channel' => $this->channel,
                        'delivery_status' => $result['status'] ?? 'pending',
                        'message_id' => $result['message_id'] ?? null,
                    ])
                    ->log('Offer letter sent via ' . $this->channel);
            } catch (Throwable $e) {
                $offer->update([
                    'delivery_status' => 'failed',
                ]);
                Log::error('OfferLetter delivery failed', [
                    'offer_uuid' => $offer->uuid,
                    'channel' => $this->channel,
                    'error' => $e->getMessage(),
                ]);
                activity()
                    ->performedOn($offer)
                    ->withProperties([
                        'channel' => $this->channel,
                        'error' => $e->getMessage(),
                    ])
                    ->log('Offer letter delivery failed');
            }
        });
    }
}
