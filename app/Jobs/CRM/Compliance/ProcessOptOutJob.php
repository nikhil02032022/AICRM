<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Compliance;

use App\Services\CRM\Compliance\OptOutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CR-003 — Opt-out honoured within 24 hours of request
class ProcessOptOutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('crm-compliance');
    }

    public function handle(OptOutService $service): void
    {
        foreach ($service->getPending() as $log) {
            $service->process($log);
        }
    }
}
