<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateWebFormDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreWebFormRequest;
use App\Http\Requests\Api\CRM\UpdateWebFormRequest;
use App\Models\CRM\WebForm;
use App\Repositories\CRM\WebForm\WebFormRepositoryInterface;
use App\Services\CRM\WebForm\WebFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-LC-001 — Web controller for WebForm management (session auth — CRM staff only)
// Returns view()/redirect() — never JsonResource or JsonResponse
final class WebFormWebController extends Controller
{
    public function __construct(
        private readonly WebFormService             $service,
        private readonly WebFormRepositoryInterface $repository,
    ) {}

    /**
     * BRD: CRM-LC-001 — Form management index.
     */
    public function index(Request $request): View
    {
        Gate::authorize('crm.forms.view');

        $forms = $this->repository->paginate(
            filters: $request->only(['is_active', 'search']),
            perPage: 20,
        );

        return view('crm.forms.index', compact('forms'));
    }

    /**
     * BRD: CRM-LC-001 — Show form builder UI.
     */
    public function create(): View
    {
        Gate::authorize('crm.forms.create');

        return view('crm.forms.create');
    }

    /**
     * BRD: CRM-LC-001 — Persist new form from builder.
     */
    public function store(StoreWebFormRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->institution_id === null) {
            return redirect()->back()->withInput()
                ->with('error', 'Your account is not linked to an institution. Please log in as an institution user (e.g. admin@demo.edu) to create web forms.');
        }

        $validated = $request->validated();

        // Auto-generate slug from name if not manually set
        if (empty($validated['slug'])) {
            $validated['slug'] = $this->service->generateUniqueSlug($validated['name'], $user->institution_id);
        }

        $dto = CreateWebFormDTO::fromRequest($validated);
        $form = $this->service->create($dto, $user->institution_id);

        return redirect()
            ->route('crm.forms.embed-code', $form->uuid)
            ->with('success', 'Web form "' . $form->name . '" created. Copy your embed code or QR below.');
    }

    /**
     * BRD: CRM-LC-001 — Edit form builder.
     */
    public function edit(WebForm $form): View
    {
        Gate::authorize('crm.forms.edit');

        return view('crm.forms.edit', compact('form'));
    }

    /**
     * BRD: CRM-LC-001 — Update form configuration.
     */
    public function update(UpdateWebFormRequest $request, WebForm $form): RedirectResponse
    {
        $this->service->update($form, $request->validated());

        return redirect()
            ->route('crm.forms.index')
            ->with('success', 'Form updated successfully.');
    }

    /**
     * BRD: CRM-LC-001 + LC-009 — Show embed code and QR download page.
     */
    public function embedCode(WebForm $form): View
    {
        Gate::authorize('crm.forms.view');

        $embedSnippet = $this->service->generateEmbedSnippet($form);

        return view('crm.forms.embed-code', compact('form', 'embedSnippet'));
    }

    /**
     * BRD: CRM-LC-001 — Preview any form (draft or published) — auth only, no is_active check.
     * Renders the same public form view with a preview banner and submissions disabled.
     */
    public function preview(WebForm $form): View
    {
        Gate::authorize('crm.forms.view');

        return view('public.form.show', [
            'form'        => $form,
            'previewMode' => true,
        ]);
    }
}
