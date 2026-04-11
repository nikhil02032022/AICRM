{{-- BRD: CRM-LC-019 — Merge history tab panel --}}
<div x-show="tab === 'merge'" role="tabpanel" class="p-5">

    @if($lead->isMerged())
        {{-- Secondary view: tombstone --}}
        <div class="rounded-xl border border-rose-100 bg-rose-50 p-5 text-center">
            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-rose-100">
                <svg class="h-5 w-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-rose-800">This lead has been merged</h4>
            <p class="mt-1 text-xs text-rose-600">
                This record was merged into a primary lead
                @if($lead->merged_at)
                    on {{ $lead->merged_at->format('d M Y \a\t H:i') }}
                @endif
                and is now archived.
            </p>
            @if($lead->merged_into_uuid)
            <a href="{{ route('crm.leads.show', $lead->merged_into_uuid) }}"
               class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                View primary lead →
            </a>
            @endif
        </div>

    @else
        {{-- Primary view: list all leads merged into this one (from MERGE activities) --}}
        @php
            $mergeActivities = $lead->activities->filter(fn ($a) => $a->type === \App\Enums\CRM\ActivityType::MERGE);
        @endphp

        @if($mergeActivities->isEmpty())
            <div class="py-12 text-center">
                <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <p class="text-sm font-medium text-gray-400">No merges yet</p>
                <p class="mt-0.5 text-xs text-gray-400">When duplicate leads are merged into this record, the history will appear here.</p>
            </div>
        @else
            <h4 class="mb-4 text-sm font-semibold text-gray-700">Merge History</h4>
            <ul class="space-y-3">
                @foreach($mergeActivities as $mergeActivity)
                <li class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-rose-100">
                        <svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800">{{ $mergeActivity->body }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ $mergeActivity->created_at?->format('d M Y \a\t H:i') }}
                            @if($mergeActivity->performedBy)
                                &middot; by {{ $mergeActivity->performedBy->name }}
                            @else
                                &middot; system
                            @endif
                        </p>
                        @if(!empty($mergeActivity->metadata['merged_secondary_uuid']))
                        <a href="{{ route('crm.leads.show', $mergeActivity->metadata['merged_secondary_uuid']) }}"
                           class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-rose-600 hover:text-rose-800 transition-colors">
                            View archived lead →
                        </a>
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>
