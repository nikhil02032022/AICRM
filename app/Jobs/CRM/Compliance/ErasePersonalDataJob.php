<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Compliance;

use App\Services\CRM\Compliance\PiiErasureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CR-005 — PII anonymised within 30 days of verified erasure request
class ErasePersonalDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('crm-compliance');
    }

    public function handle(PiiErasureService $service): void
    {
        foreach ($service->getDue() as $request) {
            try {
                $service->erase($request);
            } catch (\Throwable $e) {
                \Log::error('PII erasure failed', [
                    'request_id' => $request->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
