<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreIvrConfigRequest;
use App\Http\Requests\CRM\UpdateIvrConfigRequest;
use App\Models\CRM\IvrConfig;
use App\Models\User;
use App\Services\CRM\Communication\IvrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// BRD: CRM-CC-019 — IVR configuration per institution/campus (web)
final class IvrConfigWebController extends Controller
{
    public function __construct(
        private readonly IvrService $ivrService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.settings.manage');

        $configs = IvrConfig::with('fallbackCounsellor')->orderByDesc('created_at')->paginate(20);

        return view('crm.settings.ivr.index', compact('configs'));
    }

    public function create(): View
    {
        $this->authorize('crm.settings.manage');

        $counsellors = User::where('institution_id', auth()->user()->institution_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('crm.settings.ivr.create', compact('counsellors'));
    }

    public function store(StoreIvrConfigRequest $request): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $config = $this->ivrService->saveConfig($request->validated());

        return redirect()
            ->route('crm.settings.ivr.show', $config->uuid)
            ->with('success', 'IVR configuration saved.');
    }

    public function show(IvrConfig $ivrConfig): View
    {
        $this->authorize('crm.settings.manage');

        return view('crm.settings.ivr.show', compact('ivrConfig'));
    }

    public function edit(IvrConfig $ivrConfig): View
    {
        $this->authorize('crm.settings.manage');

        $counsellors = User::where('institution_id', auth()->user()->institution_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('crm.settings.ivr.edit', compact('ivrConfig', 'counsellors'));
    }

    public function update(UpdateIvrConfigRequest $request, IvrConfig $ivrConfig): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $this->ivrService->saveConfig($request->validated(), $ivrConfig);

        return redirect()
            ->route('crm.settings.ivr.show', $ivrConfig->uuid)
            ->with('success', 'IVR configuration updated.');
    }

    public function toggleActive(IvrConfig $ivrConfig): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $ivrConfig->update(['is_active' => ! $ivrConfig->is_active]);

        $status = $ivrConfig->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "IVR configuration {$status}.");
    }

    public function destroy(IvrConfig $ivrConfig): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $ivrConfig->delete();

        return redirect()
            ->route('crm.settings.ivr.index')
            ->with('success', 'IVR configuration removed.');
    }
}
