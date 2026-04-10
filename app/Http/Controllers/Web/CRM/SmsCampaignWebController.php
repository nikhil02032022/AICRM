<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\DltTemplate;
use App\Models\CRM\SmsCampaign;
use App\Repositories\CRM\Communication\EmailCampaignRepositoryInterface;
use App\Services\CRM\Communication\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-CC-006 — SMS campaign management (web)
final class SmsCampaignWebController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.campaigns.send');

        $campaigns = SmsCampaign::orderByDesc('created_at')->paginate(25);

        return view('crm.communication.sms.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('crm.campaigns.send');

        $dltTemplates = DltTemplate::whereNotNull('approved_at')
            ->orderBy('template_name')
            ->get(['id', 'template_name', 'dlt_template_id']);

        return view('crm.communication.sms.campaigns.create', compact('dltTemplates'));
    }

    public function store(): RedirectResponse
    {
        $this->authorize('crm.campaigns.send');

        $user = Auth::user();
        $data = request()->validate([
            'name'          => ['required', 'string', 'max:100'],
            'dlt_template_id' => ['required', 'exists:dlt_templates,id'],
            'gateway'       => ['required', 'string'],
            'recipient_filter' => ['nullable', 'array'],
            'scheduled_at'  => ['nullable', 'date', 'after:now'],
        ]);

        $campaign = SmsCampaign::create([
            ...$data,
            'institution_id' => $user->institution_id,
            'created_by'     => $user->id,
        ]);

        return redirect()
            ->route('crm.communication.sms.campaigns.index')
            ->with('success', 'SMS campaign created.');
    }

    public function launch(SmsCampaign $campaign): RedirectResponse
    {
        $this->authorize('crm.campaigns.send');

        if (! $campaign->status->isEditable()) {
            return back()->with('error', 'Campaign cannot be launched in current state.');
        }

        $this->smsService->dispatchSmsCampaign($campaign);

        return back()->with('success', 'SMS campaign launched.');
    }
}
