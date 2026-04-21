<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Scholarships;

use App\Enums\CRM\Scholarships\ApprovalStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Scholarships\DecideScholarshipAwardRequest;
use App\Http\Requests\CRM\Scholarships\SubmitScholarshipAwardRequest;
use App\Models\CRM\Scholarships\ScholarshipAward;
use App\Models\CRM\Scholarships\ScholarshipCategory;
use App\Services\CRM\Scholarships\ScholarshipAwardService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-FM-008
class ScholarshipAwardController extends Controller
{
    public function __construct(private readonly ScholarshipAwardService $service) {}

    public function index(): View
    {
        $awards = ScholarshipAward::query()
            ->with(['category', 'application'])
            ->orderByDesc('id')
            ->paginate(20);

        $categories = ScholarshipCategory::query()->where('is_active', true)->orderBy('name')->get();

        $applications = \App\Models\CRM\Application::query()
            ->with('lead')
            ->orderByDesc('submitted_at')
            ->get();

        return view('crm.scholarships.awards.index', compact('awards', 'categories', 'applications'));
    }

    public function store(SubmitScholarshipAwardRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = ScholarshipCategory::findOrFail($data['scholarship_category_id']);

        $award = ScholarshipAward::create([
            'institution_id'          => Auth::user()?->institution_id,
            'application_uuid'        => $data['application_uuid'],
            'scholarship_category_id' => $category->id,
            'amount'                  => $data['amount'],
            'status'                  => 'draft',
            'current_stage'           => ApprovalStage::COUNSELLOR->value,
            'requested_by'            => Auth::id(),
        ]);

        $this->service->submit($award);

        return back()->with('status', 'Scholarship award submitted.');
    }

    public function decide(DecideScholarshipAwardRequest $request, ScholarshipAward $award): RedirectResponse
    {
        $stage = $award->current_stage instanceof ApprovalStage ? $award->current_stage : ApprovalStage::from((string) $award->current_stage);

        $this->authoriseStage($stage);

        $data = $request->validated();
        if ($data['decision'] === 'approve') {
            $this->service->approve($award, $stage, $data['comment'] ?? null);
        } else {
            $this->service->reject($award, $stage, $data['reason'] ?? 'No reason supplied.');
        }

        return back()->with('status', 'Decision recorded.');
    }

    private function authoriseStage(ApprovalStage $stage): void
    {
        $user = Auth::user();
        $can = match ($stage) {
            ApprovalStage::MANAGER => $user?->can('scholarship.award.approve.manager'),
            ApprovalStage::FINANCE => $user?->can('scholarship.award.approve.finance'),
            default => false,
        };
        if (! $can) {
            throw new AuthorizationException('You are not allowed to decide this stage.');
        }
    }
}
