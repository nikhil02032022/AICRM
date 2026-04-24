<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Admin;

use App\Enums\CRM\Admin\BackupStatus;
use App\Models\CRM\Admin\BackupLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

// BRD: CRM-SA-012 — Backup with configurable frequency
class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(private readonly ?int $institutionId = null)
    {
        $this->onQueue('crm-admin');
    }

    public function handle(): void
    {
        $filename = 'backup-'.now()->format('Y-m-d-His').'.sql.gz';
        $disk     = 'local';
        $path     = 'backups/'.($this->institutionId ?? 'system').'/'.$filename;

        $log = BackupLog::create([
            'institution_id' => $this->institutionId,
            'filename'       => $filename,
            'disk'           => $disk,
            'status'         => BackupStatus::Running->value,
            'started_at'     => now(),
        ]);

        try {
            $dbConfig = config('database.connections.'.config('database.default'));
            $host     = $dbConfig['host'] ?? '127.0.0.1';
            $dbName   = $dbConfig['database'];
            $user     = $dbConfig['username'];
            $pass     = $dbConfig['password'];

            $fullPath = Storage::disk($disk)->path($path);
            $dir      = dirname($fullPath);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s | gzip > %s',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbName),
                escapeshellarg($fullPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \RuntimeException('mysqldump failed with exit code '.$returnCode);
            }

            $sizeBytes = filesize($fullPath) ?: null;

            $log->update([
                'status'       => BackupStatus::Completed->value,
                'size_bytes'   => $sizeBytes,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status'        => BackupStatus::Failed->value,
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            throw $e;
        }
    }
}
