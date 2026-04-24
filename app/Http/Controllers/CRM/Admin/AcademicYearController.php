<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\Admin\AcademicYear;
use App\Services\CRM\Admin\AcademicYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SA-003 — Academic year / admission cycle management
final class AcademicYearController extends Controller
{
    public function __construct(private readonly AcademicYearService $service) {}

    public function index(): View
    {
        $this->authorize('crm.admin.academic-years.manage');

        $years = AcademicYear::orderByDesc('start_date')->paginate(20);

        return view('crm.admin.academic-years.index', compact('years'));
    }

    public function create(): View
    {
        $this->authorize('crm.admin.academic-years.manage');

        return view('crm.admin.academic-years.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('crm.admin.academic-years.manage');

        $validated = $request->validate([
            'label'      => 'required|string|max:30',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $validated['institution_id'] = $request->user()->institution_id;

        $this->service->create($validated);

        return redirect()->route('crm.admin.academic-years.index')
            ->with('success', 'Academic year created successfully.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        $this->authorize('crm.admin.academic-years.manage');

        return view('crm.admin.academic-years.edit', ['year' => $academicYear]);
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('crm.admin.academic-years.manage');

        $validated = $request->validate([
            'label'      => 'required|string|max:30',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $this->service->update($academicYear, $validated);

        return redirect()->route('crm.admin.academic-years.index')
            ->with('success', 'Academic year updated.');
    }

    public function rollover(AcademicYear $academicYear, Request $request): RedirectResponse
    {
        $this->authorize('crm.admin.academic-years.manage');

        $validated = $request->validate([
            'new_year_label' => 'required|string|max:30',
        ]);

        $this->service->rollover($academicYear, $validated['new_year_label']);

        return redirect()->route('crm.admin.academic-years.index')
            ->with('success', "Rolled over to {$validated['new_year_label']} successfully.");
    }

    public function activate(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('crm.admin.academic-years.manage');

        $this->service->activate($academicYear);

        return redirect()->route('crm.admin.academic-years.index')
            ->with('success', "Academic year [{$academicYear->label}] is now active.");
    }
}
