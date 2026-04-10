<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreDltTemplateRequest;
use App\Models\CRM\DltTemplate;
use App\Services\CRM\Communication\DltTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-CC-008 — DLT template registration workflow (web)
final class DltTemplateWebController extends Controller
{
    public function __construct(
        private readonly DltTemplateService $dltService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.communication.templates.manage');

        $templates = $this->dltService->paginate(request()->only(['gateway', 'status']));

        return view('crm.communication.sms.dlt.index', compact('templates'));
    }

    public function create(): View
    {
        $this->authorize('crm.communication.templates.manage');

        return view('crm.communication.sms.dlt.create');
    }

    public function store(StoreDltTemplateRequest $request): RedirectResponse
    {
        $user     = Auth::user();
        $template = $this->dltService->create($request->validated(), $user->institution_id);

        return redirect()
            ->route('crm.communication.sms.dlt.templates.index')
            ->with('success', 'DLT template saved as draft.');
    }

    public function submitForApproval(DltTemplate $template): RedirectResponse
    {
        $this->authorize('crm.communication.templates.manage');
        $this->dltService->submitForApproval($template);

        return back()->with('success', 'Template submitted for DLT approval.');
    }

    public function markApproved(DltTemplate $template): RedirectResponse
    {
        $this->authorize('crm.communication.templates.manage');
        $dltId = request()->validate(['dlt_template_id' => ['required', 'string']])['dlt_template_id'];
        $this->dltService->markApproved($template, $dltId);

        return back()->with('success', 'DLT template marked as approved.');
    }

    public function markRejected(DltTemplate $template): RedirectResponse
    {
        $this->authorize('crm.communication.templates.manage');
        $notes = request()->validate(['notes' => ['required', 'string']])['notes'];
        $this->dltService->markRejected($template, $notes);

        return back()->with('error', 'DLT template marked as rejected.');
    }
}
