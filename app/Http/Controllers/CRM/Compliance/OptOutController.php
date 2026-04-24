<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Compliance;

use App\Http\Controllers\Controller;
use App\Models\CRM\Compliance\OptOutLog;
use App\Services\CRM\Compliance\OptOutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-CR-003 — Opt-out/unsubscribe honoured within 24 hours
final class OptOutController extends Controller
{
    public function __construct(private readonly OptOutService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.compliance.opt-out.view');

        $base = OptOutLog::where('institution_id', $request->user()->institution_id)
            ->with('lead:id,first_name,last_name,uuid')
            ->orderByDesc('requested_at');

        $pendingLogs   = (clone $base)->whereNull('processed_at')->paginate(50)->withQueryString();
        $processedLogs = (clone $base)->whereNotNull('processed_at')->paginate(50, ['*'], 'processed_page')->withQueryString();
        $pendingCount  = $pendingLogs->total();

        return view('crm.compliance.opt-out.index', compact('pendingLogs', 'processedLogs', 'pendingCount'));
    }

    public function processManual(OptOutLog $optOutLog): RedirectResponse
    {
        $this->authorize('crm.compliance.opt-out.process');

        $this->service->process($optOutLog);

        return redirect()->route('crm.compliance.opt-out.index')
            ->with('success', 'Opt-out processed and lead communication preferences updated.');
    }
}
