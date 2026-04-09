<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\IntegrationChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CRM\StoreIntegrationCredentialRequest;
use App\Models\CRM\IntegrationCredential;
use App\Repositories\CRM\Import\IntegrationCredentialRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-SA-010 — Web controller for integration credential management (settings UI)
// OWASP A05 — credentials stored encrypted; never returned in views or logs
final class IntegrationWebController extends Controller
{
    public function __construct(
        private readonly IntegrationCredentialRepositoryInterface $repository,
    ) {}

    /**
     * GET /crm/settings/integrations
     */
    public function index(): View
    {
        Gate::authorize('crm.integrations.view');

        $credentials = $this->repository->allForInstitution(
            auth()->user()->institution_id,
        );

        return view('crm.settings.integrations.index', [
            'credentials'    => $credentials,
            'channelOptions' => IntegrationChannel::optionsForSelect(),
        ]);
    }

    /**
     * GET /crm/settings/integrations/create
     */
    public function create(): View
    {
        Gate::authorize('crm.integrations.manage');

        return view('crm.settings.integrations.create', [
            'channelOptions' => IntegrationChannel::optionsForSelect(),
        ]);
    }

    /**
     * POST /crm/settings/integrations
     */
    public function store(StoreIntegrationCredentialRequest $request): RedirectResponse
    {
        Gate::authorize('crm.integrations.manage');

        $validated = $request->validated();

        $this->repository->create([
            'channel'     => $validated['channel'],
            'label'       => $validated['label'],
            'credentials' => $validated['credentials'] ?? [],
            'is_active'   => (bool) ($validated['is_active'] ?? true),
        ], $request->user()->institution_id);

        return redirect()
            ->route('crm.settings.integrations.index')
            ->with('success', 'Integration credential saved successfully.');
    }

    /**
     * GET /crm/settings/integrations/{integration:uuid}/edit
     */
    public function edit(IntegrationCredential $integration): View
    {
        Gate::authorize('crm.integrations.manage');

        return view('crm.settings.integrations.edit', [
            'integration'    => $integration,
            'channelOptions' => IntegrationChannel::optionsForSelect(),
        ]);
    }

    /**
     * PUT /crm/settings/integrations/{integration:uuid}
     */
    public function update(StoreIntegrationCredentialRequest $request, IntegrationCredential $integration): RedirectResponse
    {
        Gate::authorize('update', $integration);

        $validated = $request->validated();

        // Merge: keep existing credential values for any field left blank on the form.
        $incomingCredentials = array_filter(
            $validated['credentials'] ?? [],
            static fn ($v): bool => $v !== null && $v !== '',
        );

        $this->repository->update($integration, [
            'label'       => $validated['label'],
            'credentials' => array_merge($integration->credentials ?? [], $incomingCredentials),
            'is_active'   => (bool) ($validated['is_active'] ?? $integration->is_active),
        ]);

        return redirect()
            ->route('crm.settings.integrations.index')
            ->with('success', 'Integration updated successfully.');
    }

    /**
     * DELETE /crm/settings/integrations/{integration:uuid}
     * Soft-deletes — credentials are not hard-deleted immediately.
     */
    public function destroy(IntegrationCredential $integration): RedirectResponse
    {
        Gate::authorize('delete', $integration);

        $this->repository->softDelete($integration);

        return redirect()
            ->route('crm.settings.integrations.index')
            ->with('success', 'Integration removed.');
    }
}
