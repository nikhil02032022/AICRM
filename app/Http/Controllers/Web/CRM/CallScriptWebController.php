<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\ResolveCallScriptBranchRequest;
use App\Http\Requests\CRM\StoreCallScriptRequest;
use App\Models\CRM\CallScript;
use App\Services\CRM\Communication\CallScriptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-TC-002 — Web controller for call script CRUD and branching simulation
final class CallScriptWebController extends Controller
{
    public function __construct(
        private readonly CallScriptService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.communication.send');

        $scripts = $this->service->paginate($request->only(['status', 'search']), 20);

        return view('crm.communication.voice.call-script', [
            'scripts' => $scripts,
            'activeScript' => null,
            'currentStep' => null,
            'nextStep' => null,
            'runnerResponse' => null,
        ]);
    }

    public function store(StoreCallScriptRequest $request): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $script = $this->service->create(
            payload: $request->validated(),
            institutionId: (int) $request->user()->institution_id,
            createdBy: (int) $request->user()->id,
        );

        return redirect()
            ->route('crm.communication.voice.scripts.show', $script->uuid)
            ->with('success', 'Call script saved successfully.');
    }

    public function show(CallScript $callScript): View
    {
        $this->authorize('crm.communication.send');

        $scripts = $this->service->paginate([], 20);
        $activeScript = $callScript->load('steps');
        $currentStep = $this->service->firstStep($activeScript);

        return view('crm.communication.voice.call-script', [
            'scripts' => $scripts,
            'activeScript' => $activeScript,
            'currentStep' => $currentStep,
            'nextStep' => null,
            'runnerResponse' => null,
        ]);
    }

    public function update(StoreCallScriptRequest $request, CallScript $callScript): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $this->service->update($callScript, $request->validated());

        return back()->with('success', 'Call script updated successfully.');
    }

    public function destroy(CallScript $callScript): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $this->service->delete($callScript);

        return redirect()
            ->route('crm.communication.voice.scripts.index')
            ->with('success', 'Call script archived successfully.');
    }

    public function resolve(ResolveCallScriptBranchRequest $request, CallScript $callScript): View
    {
        $this->authorize('crm.communication.send');

        $scripts = $this->service->paginate([], 20);
        $activeScript = $callScript->load('steps');
        $currentStep = $this->service->stepByKey($activeScript, (string) $request->validated('current_step_key'));
        $nextStep = $this->service->resolveNextStep(
            script: $activeScript,
            currentStepKey: (string) $request->validated('current_step_key'),
            response: $request->validated('response'),
        );

        return view('crm.communication.voice.call-script', [
            'scripts' => $scripts,
            'activeScript' => $activeScript,
            'currentStep' => $currentStep,
            'nextStep' => $nextStep,
            'runnerResponse' => $request->validated('response'),
        ]);
    }
}
