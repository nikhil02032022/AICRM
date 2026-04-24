<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Alumni;

use App\Models\CRM\Alumni\AlumniPipeline;
use App\Services\CRM\Alumni\AlumniPipelineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AL-001 — Async processing of alumni pipeline entries
class AlumniPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly AlumniPipeline $record)
    {
        $this->onQueue('crm-alumni');
    }

    public function handle(AlumniPipelineService $service): void
    {
        try {
            $service->markEligible($this->record);
        } catch (\Throwable $e) {
            $service->markFailed($this->record);
            throw $e;
        }
    }
}
