<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Documents;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Documents\StoreDocumentChecklistRequest;
use App\Http\Requests\CRM\Documents\UpdateDocumentChecklistRequest;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Documents\DocumentChecklist;
use App\Services\CRM\Documents\DocumentChecklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-DM-001
class DocumentChecklistController extends Controller
{
    public function __construct(private readonly DocumentChecklistService $service) {}

    public function index(): View
    {
        Gate::authorize('document.checklist.manage');
        $checklists = DocumentChecklist::query()->with(['programme', 'items'])->orderByDesc('id')->paginate(20);
        $programmes = CrmProgramme::query()->orderBy('name')->get(['id', 'name']);

        return view('crm.documents.checklists.index', [
            'checklists' => $checklists,
            'programmes' => $programmes,
        ]);
    }

    public function store(StoreDocumentChecklistRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);
        $this->service->create($data, $items);

        return redirect()->route('crm.documents.checklists.index')->with('status', 'Checklist created.');
    }

    public function update(UpdateDocumentChecklistRequest $request, DocumentChecklist $checklist): RedirectResponse
    {
        $this->service->update($checklist, $request->validated());

        return redirect()->route('crm.documents.checklists.index')->with('status', 'Checklist updated.');
    }

    public function toggle(DocumentChecklist $checklist): RedirectResponse
    {
        Gate::authorize('document.checklist.manage');
        $this->service->toggle($checklist);

        return redirect()->route('crm.documents.checklists.index')->with('status', 'Checklist toggled.');
    }
}
