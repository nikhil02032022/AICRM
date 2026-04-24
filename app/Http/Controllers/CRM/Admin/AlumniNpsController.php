<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Enums\CRM\Alumni\NpsSnapshotSource;
use App\Http\Controllers\Controller;
use App\Models\CRM\Admin\AcademicYear;
use App\Models\CRM\Alumni\AlumniNpsSnapshot;
use App\Models\CRM\CrmProgramme;
use App\Services\CRM\Alumni\AlumniNpsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AL-004 — Admin manual NPS data entry and trend view
final class AlumniNpsController extends Controller
{
    public function __construct(
        private readonly AlumniNpsService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AlumniNpsSnapshot::class);

        $institutionId = $request->user()->institution_id;
        $snapshots     = AlumniNpsSnapshot::with(['academicYear', 'programme'])
            ->orderByDesc('survey_date')
            ->paginate(20);

        $latest = $this->service->getLatestScore($institutionId);
        $trend  = $this->service->getTrend($institutionId);

        return view('crm.admin.nps.index', compact('snapshots', 'latest', 'trend'));
    }

    public function create(Request $request): View
    {
        $this->authorize('manage', AlumniNpsSnapshot::class);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $programmes    = CrmProgramme::orderBy('name')->get();

        return view('crm.admin.nps.create', compact('academicYears', 'programmes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', AlumniNpsSnapshot::class);

        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'programme_id'     => ['nullable', 'integer', 'exists:crm_programmes,id'],
            'promoters_pct'    => ['required', 'numeric', 'min:0', 'max:100'],
            'neutrals_pct'     => ['required', 'numeric', 'min:0', 'max:100'],
            'detractors_pct'   => ['required', 'numeric', 'min:0', 'max:100'],
            'survey_date'      => ['required', 'date'],
        ]);

        $validated['institution_id'] = $request->user()->institution_id;
        $validated['source']         = NpsSnapshotSource::Manual->value;

        $this->service->storeSnapshot($validated);

        return redirect()->route('crm.admin.nps.index')
            ->with('success', 'NPS snapshot saved successfully.');
    }
}
