<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Services\CRM\Communication\DiallerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-TC-001 — Async queue runner for auto-dialler sessions
final class DiallerJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 2;

    public function __construct(
        public readonly string $sessionUuid,
    ) {
        $this->onQueue('crm-telecalling');
    }

    public function uniqueId(): string
    {
        return "dialler-session-step:{$this->sessionUuid}";
    }

    public function handle(DiallerService $diallerService): void
    {
        $diallerService->processNextCall($this->sessionUuid);
    }
}
