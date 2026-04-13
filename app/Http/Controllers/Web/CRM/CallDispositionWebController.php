<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreCallDispositionConfigRequest;
use App\Models\CRM\CallDispositionConfig;
use App\Services\CRM\Communication\CallDispositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-TC-003 — Web controller for call disposition configuration
final class CallDispositionWebController extends Controller
{
    public function __construct(
        private readonly CallDispositionService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.settings.manage');

        $institutionId = (int) $request->user()->institution_id;
        $this->service->ensureDefaults($institutionId, (int) $request->user()->id);

        $configs = $this->service->paginate($institutionId, 20);

        return view('crm.communication.voice.dispositions', [
            'configs' => $configs,
        ]);
    }

    public function store(StoreCallDispositionConfigRequest $request): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $this->service->create(
            institutionId: (int) $request->user()->institution_id,
            userId: (int) $request->user()->id,
            payload: $request->validated(),
        );

        return back()->with('success', 'Call disposition added successfully.');
    }

    public function update(StoreCallDispositionConfigRequest $request, CallDispositionConfig $callDispositionConfig): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $this->service->update($callDispositionConfig, $request->validated());

        return back()->with('success', 'Call disposition updated successfully.');
    }
}
