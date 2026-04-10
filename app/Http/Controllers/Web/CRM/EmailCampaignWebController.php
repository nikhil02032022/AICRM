<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateEmailCampaignDTO;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\CreateEmailCampaignRequest;
use App\Models\CRM\CommunicationTemplate;
use App\Models\CRM\EmailCampaign;
use App\Models\CRM\SenderDomain;
use App\Repositories\CRM\Communication\EmailCampaignRepositoryInterface;
use App\Services\CRM\Communication\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-CC-002 — Email campaign management (web)
final class EmailCampaignWebController extends Controller
{
    public function __construct(
        private readonly EmailCampaignRepositoryInterface $campaignRepository,
        private readonly EmailService $emailService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.campaigns.send');

        $campaigns = $this->campaignRepository->paginate(request()->only(['status', 'search']));

        return view('crm.communication.email.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('crm.campaigns.send');

        $templates     = CommunicationTemplate::where('channel', 'EMAIL')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $senderDomains = SenderDomain::whereNotNull('verified_at')
            ->orderBy('domain')
            ->get(['id', 'domain', 'default_from_name', 'default_from_email']);

        $statuses      = LeadStatus::cases();
        $sources       = LeadSource::cases();
        $temperatures  = LeadTemperature::cases();

        return view('crm.communication.email.campaigns.create', compact(
            'templates', 'senderDomains', 'statuses', 'sources', 'temperatures'
        ));
    }

    public function store(CreateEmailCampaignRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $dto  = CreateEmailCampaignDTO::fromArray(
            $request->validated(),
            $user->institution_id,
            $user->id,
        );

        $campaign = $this->campaignRepository->create($dto);

        return redirect()
            ->route('crm.communication.email.campaigns.show', $campaign->uuid)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(EmailCampaign $campaign): View
    {
        $this->authorize('crm.campaigns.send');

        return view('crm.communication.email.campaigns.show', compact('campaign'));
    }

    public function launch(EmailCampaign $campaign): RedirectResponse
    {
        $this->authorize('crm.campaigns.send');

        if (! $campaign->status->isEditable()) {
            return back()->with('error', 'Campaign cannot be launched in its current state.');
        }

        $this->emailService->dispatchCampaign($campaign);

        return redirect()
            ->route('crm.communication.email.campaigns.show', $campaign->uuid)
            ->with('success', 'Campaign launched. Emails are being sent.');
    }
}
