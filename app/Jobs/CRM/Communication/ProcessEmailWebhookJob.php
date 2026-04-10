<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Models\CRM\CommunicationLog;
use App\Models\CRM\Lead;
use App\Services\CRM\Communication\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-003 — Process email webhook delivery events from provider
final class ProcessEmailWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 10;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly array $payload,
        public readonly string $provider,
    ) {
        $this->queue = 'crm-comms-email';
    }

    public function handle(EmailService $emailService): void
    {
        $emailService->handleDeliveryEvent($this->payload, $this->provider);
    }
}
