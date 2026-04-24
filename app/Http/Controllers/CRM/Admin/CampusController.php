<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SA-002 — Campus management within institution
class CampusController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Campus::class);

        $campuses = Campus::withoutGlobalScopes()
            ->where('institution_id', auth()->user()->institution_id)
            ->with('institution')
            ->paginate(20);

        return view('crm.admin.campuses.index', compact('campuses'));
    }

    public function create(): View
    {
        $this->authorize('create', Campus::class);

        return view('crm.admin.campuses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Campus::class);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:20'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $validated['institution_id'] = auth()->user()->institution_id;
        $validated['is_active']      = $request->boolean('is_active', true);

        Campus::create($validated);

        return redirect()->route('admin.campuses.index')
            ->with('success', 'Campus created successfully.');
    }

    public function edit(Campus $campus): View
    {
        $this->authorize('update', $campus);

        return view('crm.admin.campuses.edit', compact('campus'));
    }

    public function update(Request $request, Campus $campus): RedirectResponse
    {
        $this->authorize('update', $campus);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:20'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $campus->update($validated);

        return redirect()->route('admin.campuses.index')
            ->with('success', 'Campus updated successfully.');
    }

    public function destroy(Campus $campus): RedirectResponse
    {
        $this->authorize('delete', $campus);

        $campus->delete();

        return redirect()->route('admin.campuses.index')
            ->with('success', 'Campus deleted successfully.');
    }
}
