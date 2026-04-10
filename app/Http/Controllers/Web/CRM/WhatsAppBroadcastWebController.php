<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\CampaignStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Http\Controllers\Controller;
use App\Models\CRM\CommunicationTemplate;
use App\Models\CRM\Lead;
use App\Models\CRM\WhatsAppBroadcast;
use App\Services\CRM\Communication\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-CC-015 — WhatsApp broadcast campaign management (web)
final class WhatsAppBroadcastWebController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.campaigns.send');

        $broadcasts = WhatsAppBroadcast::with('template', 'creator')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('crm.communication.whatsapp.broadcasts.index', compact('broadcasts'));
    }

    public function create(): View
    {
        $this->authorize('crm.campaigns.send');

        $templates    = CommunicationTemplate::where('channel', 'WHATSAPP')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'body_text']);

        $statuses     = LeadStatus::cases();
        $sources      = LeadSource::cases();
        $temperatures = LeadTemperature::cases();

        return view('crm.communication.whatsapp.broadcasts.create', compact(
            'templates', 'statuses', 'sources', 'temperatures'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('crm.campaigns.send');

        $data = $request->validate([
            'name'                              => ['required', 'string', 'max:120'],
            'template_id'                       => ['required', 'exists:communication_templates,id'],
            'recipient_filter'                  => ['nullable', 'array'],
            'recipient_filter.statuses'         => ['nullable', 'array'],
            'recipient_filter.statuses.*'       => ['string'],
            'recipient_filter.temperatures'     => ['nullable', 'array'],
            'recipient_filter.temperatures.*'   => ['string'],
            'recipient_filter.sources'          => ['nullable', 'array'],
            'recipient_filter.sources.*'        => ['string'],
            'recipient_filter.date_from'        => ['nullable', 'date'],
            'recipient_filter.date_to'          => ['nullable', 'date', 'after_or_equal:recipient_filter.date_from'],
        ]);

        $user = Auth::user();

        $broadcast = WhatsAppBroadcast::create([
            'institution_id'   => $user->institution_id,
            'name'             => $data['name'],
            'template_id'      => $data['template_id'],
            'recipient_filter' => $data['recipient_filter'] ?? null,
            'status'           => CampaignStatus::DRAFT,
            'created_by'       => $user->id,
        ]);

        return redirect()
            ->route('crm.communication.whatsapp.broadcasts.show', $broadcast->uuid)
            ->with('success', 'Broadcast created. Review and launch when ready.');
    }

    public function show(WhatsAppBroadcast $broadcast): View
    {
        $this->authorize('crm.campaigns.send');

        $broadcast->load('template', 'creator');

        return view('crm.communication.whatsapp.broadcasts.show', compact('broadcast'));
    }

    public function launch(WhatsAppBroadcast $broadcast): RedirectResponse
    {
        $this->authorize('crm.campaigns.send');

        if (! $broadcast->status->isEditable()) {
            return back()->with('error', 'Broadcast cannot be launched in its current state.');
        }

        $template = $broadcast->template;

        /** @var \Illuminate\Support\Collection<int, int> $leadIds */
        $leadIds = $this->resolveRecipients($broadcast);

        if ($leadIds->isEmpty()) {
            return back()->with('error', 'No leads found matching the selected segment.');
        }

        $broadcast->update([
            'status'           => CampaignStatus::SENDING,
            'lead_count'       => $leadIds->count(),
            'dispatched_count' => $leadIds->count(),
            'launched_at'      => now(),
        ]);

        $this->whatsAppService->dispatchBroadcast($template, $leadIds->all());

        return redirect()
            ->route('crm.communication.whatsapp.broadcasts.show', $broadcast->uuid)
            ->with('success', "Broadcast launched — {$leadIds->count()} messages queued.");
    }

    /**
     * Resolve lead IDs matching the recipient_filter stored on the broadcast.
     * Only leads with a non-null mobile and no DNC/opt-out flag.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function resolveRecipients(WhatsAppBroadcast $broadcast): \Illuminate\Support\Collection
    {
        $filter = $broadcast->recipient_filter ?? [];

        $query = Lead::whereNotNull('mobile')
            ->where('do_not_contact', false)
            ->where('sms_opt_out', false);

        if (! empty($filter['statuses'])) {
            $query->whereIn('status', $filter['statuses']);
        }

        if (! empty($filter['temperatures'])) {
            $query->whereIn('temperature', $filter['temperatures']);
        }

        if (! empty($filter['sources'])) {
            $query->whereIn('source', $filter['sources']);
        }

        if (! empty($filter['date_from'])) {
            $query->whereDate('created_at', '>=', $filter['date_from']);
        }

        if (! empty($filter['date_to'])) {
            $query->whereDate('created_at', '<=', $filter['date_to']);
        }

        return $query->pluck('id');
    }
}
