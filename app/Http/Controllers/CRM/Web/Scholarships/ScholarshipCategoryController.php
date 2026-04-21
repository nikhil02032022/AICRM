<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Scholarships;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Scholarships\StoreScholarshipCategoryRequest;
use App\Http\Requests\CRM\Scholarships\UpdateScholarshipCategoryRequest;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Scholarships\ScholarshipCategory;
use App\Services\CRM\Scholarships\ScholarshipCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-FM-006
class ScholarshipCategoryController extends Controller
{
    public function __construct(private readonly ScholarshipCategoryService $service) {}

    public function index(): View
    {
        Gate::authorize('scholarship.category.manage');
        $items = ScholarshipCategory::query()->with('programme')->orderByDesc('id')->paginate(20);
        $programmes = CrmProgramme::query()->orderBy('name')->get(['id', 'name']);

        return view('crm.scholarships.categories.index', [
            'items' => $items,
            'programmes' => $programmes,
        ]);
    }

    public function store(StoreScholarshipCategoryRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('crm.scholarships.categories.index')
            ->with('status', 'Scholarship category created.');
    }

    public function update(UpdateScholarshipCategoryRequest $request, ScholarshipCategory $category): RedirectResponse
    {
        $this->service->update($category, $request->validated());

        return redirect()->route('crm.scholarships.categories.index')
            ->with('status', 'Scholarship category updated.');
    }

    public function toggle(ScholarshipCategory $category): RedirectResponse
    {
        Gate::authorize('scholarship.category.manage');
        $this->service->toggle($category);

        return redirect()->route('crm.scholarships.categories.index')
            ->with('status', 'Scholarship category toggled.');
    }
}
