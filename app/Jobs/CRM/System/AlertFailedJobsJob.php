<?php

declare(strict_types=1);

namespace App\Jobs\CRM\System;

use App\Models\User;
use App\Notifications\CRM\System\FailedJobAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

// NFR-AV-002 — Daily check for failed jobs; alerts admins when threshold is exceeded.
final class AlertFailedJobsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const THRESHOLD = 5;

    public function __construct()
    {
        $this->queue = 'crm-default';
    }

    public function handle(): void
    {
        $failedCount = DB::table('failed_jobs')->count();

        if ($failedCount < self::THRESHOLD) {
            return;
        }

        $oldest = DB::table('failed_jobs')->orderBy('failed_at')->first();

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new FailedJobAlertNotification($failedCount, $oldest?->failed_at));
        }
    }
}
