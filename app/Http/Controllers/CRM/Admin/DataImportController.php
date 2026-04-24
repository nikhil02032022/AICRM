<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\Admin\DataImportJob;
use App\Services\CRM\Admin\DataImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SA-005 — Data import (leads, applications, contacts)
final class DataImportController extends Controller
{
    public function __construct(private readonly DataImportService $service) {}

    public function index(): View
    {
        $this->authorize('crm.admin.data-import.manage');

        return view('crm.admin.data-import.index');
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->authorize('crm.admin.data-import.manage');

        $validated = $request->validate([
            'entity' => 'required|in:leads,applications,contacts',
            'file'   => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $path = $request->file('file')->store('imports/'.now()->format('Y-m-d'));

        DataImportJob::dispatch(
            $path,
            $validated['entity'],
            $request->user()->institution_id,
            $request->user()->id,
        );

        return redirect()->route('crm.admin.data-import.index')
            ->with('success', 'File uploaded and queued for processing. You will receive a notification when complete.');
    }
}
