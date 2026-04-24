<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Admin;

use App\Models\User;
use App\Services\CRM\Admin\DataImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// BRD: CRM-SA-005 — Background import processing
class DataImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        private readonly string $filePath,
        private readonly string $entity,
        private readonly int $institutionId,
        private readonly int $initiatorId,
    ) {
        $this->onQueue('crm-admin');
    }

    public function handle(DataImportService $service): void
    {
        $tempFile = Storage::path($this->filePath);

        $result = match ($this->entity) {
            'leads' => $service->importLeads(
                new \Illuminate\Http\UploadedFile($tempFile, basename($tempFile)),
                $this->institutionId
            ),
            default => ['imported' => 0, 'errors' => ['Unsupported entity type']],
        };

        $initiator = User::find($this->initiatorId);
        if ($initiator) {
            // Notify initiator — in production would send an email with $result summary
            \Log::info('DataImportJob completed', [
                'user'       => $initiator->email,
                'entity'     => $this->entity,
                'imported'   => $result['imported'],
                'error_count' => count($result['errors']),
            ]);
        }
    }
}
