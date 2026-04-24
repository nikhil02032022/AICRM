<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\Admin\DatabaseBackupJob;
use App\Models\CRM\Admin\BackupLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

// BRD: CRM-SA-012 — Backup and restore with configurable frequency
final class BackupController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('crm.admin.backups.manage');

        $logs = BackupLog::where('institution_id', $request->user()->institution_id)
            ->orWhereNull('institution_id')
            ->orderByDesc('started_at')
            ->paginate(20);

        return view('crm.admin.backups.index', compact('logs'));
    }

    public function trigger(Request $request): RedirectResponse
    {
        $this->authorize('crm.admin.backups.manage');

        DatabaseBackupJob::dispatch($request->user()->institution_id);

        return redirect()->route('crm.admin.backups.index')
            ->with('success', 'Backup job queued. It will appear in this list shortly.');
    }

    public function download(BackupLog $backupLog): StreamedResponse
    {
        $this->authorize('crm.admin.backups.manage');

        $path = 'backups/'.($backupLog->institution_id ?? 'system').'/'.$backupLog->filename;

        abort_unless(Storage::disk($backupLog->disk)->exists($path), 404);

        return Storage::disk($backupLog->disk)->download($path, $backupLog->filename);
    }
}
