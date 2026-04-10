<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateCommunicationTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreCommunicationTemplateRequest;
use App\Models\CRM\CommunicationTemplate;
use App\Services\CRM\Communication\TemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-CC-001 — Communication template CRUD (web)
final class CommunicationTemplateWebController extends Controller
{
    public function __construct(
        private readonly TemplateService $templateService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.communication.templates.manage');

        $templates = $this->templateService->paginate(request()->only(['channel', 'type', 'search', 'is_active']));

        return view('crm.communication.templates.index', compact('templates'));
    }

    public function create(): View
    {
        $this->authorize('crm.communication.templates.manage');

        return view('crm.communication.templates.create');
    }

    public function store(StoreCommunicationTemplateRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $dto  = CreateCommunicationTemplateDTO::fromArray(
            $request->validated(),
            $user->institution_id,
            $user->id,
        );

        $template = $this->templateService->create($dto);

        return redirect()
            ->route('crm.communication.templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(CommunicationTemplate $template): View
    {
        $this->authorize('crm.communication.templates.manage');

        return view('crm.communication.templates.edit', compact('template'));
    }

    public function update(StoreCommunicationTemplateRequest $request, CommunicationTemplate $template): RedirectResponse
    {
        $this->templateService->update($template, $request->validated());

        return redirect()
            ->route('crm.communication.templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(CommunicationTemplate $template): RedirectResponse
    {
        $this->authorize('crm.communication.templates.manage');
        $this->templateService->delete($template);

        return redirect()
            ->route('crm.communication.templates.index')
            ->with('success', 'Template deleted.');
    }
}
