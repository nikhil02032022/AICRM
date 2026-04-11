<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreLeadAttributionTouchpointRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Marketing\AttributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-LC-016 — Web controller for attribution ledger viewing and touchpoint capture.
final class AttributionWebController extends Controller
{
    public function __construct(
        private readonly AttributionService $attributionService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.campaigns.manage');

        $filters = [
            'lead_name' => (string) $request->input('lead_name', ''),
            'source' => (string) $request->input('source', ''),
            'created_from' => (string) $request->input('created_from', ''),
            'created_to' => (string) $request->input('created_to', ''),
        ];

        $leadOptions = Lead::query()
            ->select(['id', 'uuid', 'first_name', 'last_name', 'source', 'created_at'])
            ->when(
                $filters['lead_name'] !== '',
                fn ($query) => $query->where(function ($builder) use ($filters): void {
                    $builder
                        ->where('first_name', 'like', '%'.$filters['lead_name'].'%')
                        ->orWhere('last_name', 'like', '%'.$filters['lead_name'].'%');
                }),
            )
            ->when(
                $filters['source'] !== '',
                fn ($query) => $query->where('source', $filters['source']),
            )
            ->when(
                $filters['created_from'] !== '',
                fn ($query) => $query->whereDate('created_at', '>=', $filters['created_from']),
            )
            ->when(
                $filters['created_to'] !== '',
                fn ($query) => $query->whereDate('created_at', '<=', $filters['created_to']),
            )
            ->latest('created_at')
            ->limit(25)
            ->get();

        $lead = null;
        $timeline = collect();

        if ($request->filled('lead_uuid')) {
            $lead = Lead::query()->where('uuid', (string) $request->input('lead_uuid'))->first();

            if ($lead !== null) {
                $timeline = collect($this->attributionService->timelineForLead($lead));
            }
        }

        return view('crm.marketing.attribution.index', [
            'lead' => $lead,
            'timeline' => $timeline,
            'leadUuid' => (string) $request->input('lead_uuid', ''),
            'filters' => $filters,
            'leadOptions' => $leadOptions,
        ]);
    }

    public function store(StoreLeadAttributionTouchpointRequest $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $this->attributionService->addTouchpoint(
            lead: $lead,
            payload: $request->validated(),
            createdBy: (int) $request->user()->id,
        );

        return redirect()
            ->route('crm.marketing.attribution.index', ['lead_uuid' => $lead->uuid])
            ->with('success', 'Touchpoint added and attribution credits recalculated.');
    }
}
