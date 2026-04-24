<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SA-001 — Institution profile management
class InstitutionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Institution::class);

        $institutions = Institution::withoutGlobalScopes()
            ->when(! auth()->user()->can('crm.admin.institutions.view.all'), function ($q) {
                $q->where('id', auth()->user()->institution_id);
            })
            ->paginate(20);

        return view('crm.admin.institutions.index', compact('institutions'));
    }

    public function show(Institution $institution): View
    {
        $this->authorize('view', $institution);

        return view('crm.admin.institutions.show', compact('institution'));
    }

    public function edit(Institution $institution): View
    {
        $this->authorize('update', $institution);

        return view('crm.admin.institutions.edit', compact('institution'));
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['nullable', 'email', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'address'       => ['nullable', 'string', 'max:500'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'country'       => ['nullable', 'string', 'max:100'],
            'timezone'      => ['nullable', 'string', 'max:50'],
            'locale'        => ['nullable', 'string', 'max:10'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'logo_url'      => ['nullable', 'url', 'max:500'],
        ]);

        $institution->update($validated);

        return redirect()->route('admin.institutions.edit', $institution)
            ->with('success', 'Institution profile updated successfully.');
    }
}
