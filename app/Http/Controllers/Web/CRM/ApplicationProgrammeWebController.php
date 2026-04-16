<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreApplicationProgrammeRequest;
use App\Models\CRM\CrmProgramme;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

// BRD: CRM-AP-005 — Manage institution programme catalogue for multi-programme application selection
final class ApplicationProgrammeWebController extends Controller
{
    public function index(): View
    {
        Gate::authorize('crm.applications.view');

        $programmes = CrmProgramme::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('crm.applications.programmes.index', compact('programmes'));
    }

    public function store(StoreApplicationProgrammeRequest $request): RedirectResponse
    {
        Gate::authorize('crm.applications.edit');

        /** @var User $user */
        $user = $request->user();

        if ($user->institution_id === null) {
            return back()->withInput()->with('error', 'Your account is not linked to an institution.');
        }

        $validated = $request->validated();

        CrmProgramme::withoutGlobalScopes()->create([
            'institution_id' => $user->institution_id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'level' => $validated['level'] ?? null,
            'department' => $validated['department'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'erp_programme_uuid' => $validated['erp_programme_uuid'] ?? (string) Str::uuid(),
        ]);

        return redirect()->route('crm.applications.programmes.index')
            ->with('success', 'Programme added to catalogue successfully.');
    }
}
