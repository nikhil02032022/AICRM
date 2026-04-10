<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Services\CRM\Communication\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-009 — Process SMS delivery receipt from gateway webhook
final class ProcessSmsDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 10;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly array $payload,
        public readonly string $gateway,
    ) {
        $this->queue = 'crm-comms-sms';
    }

    public function handle(SmsService $smsService): void
    {
        $smsService->handleDeliveryReceipt($this->payload, $this->gateway);
    }
}
