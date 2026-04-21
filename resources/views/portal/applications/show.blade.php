<x-layouts.portal-app :title="$application->programme?->name ?? 'Application'" :applicant="$applicant">
    <x-slot:header>
        <div class="flex items-center gap-2">
            <a href="{{ route('portal.applications.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 transition-colors">My Applications</a>
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
            <h1 class="text-lg font-semibold text-gray-800 truncate">
                {{ $application->programme?->name ?? 'Application' }}
            </h1>
        </div>
    </x-slot:header>

    @php
        $colour  = $application->status->badgeColour();
        $colours = [
            'blue'    => 'bg-blue-100 text-blue-800',
            'cyan'    => 'bg-cyan-100 text-cyan-800',
            'yellow'  => 'bg-yellow-100 text-yellow-800',
            'purple'  => 'bg-purple-100 text-purple-800',
            'green'   => 'bg-green-100 text-green-800',
            'orange'  => 'bg-orange-100 text-orange-800',
            'emerald' => 'bg-emerald-100 text-emerald-800',
            'slate'   => 'bg-slate-100 text-slate-700',
            'red'     => 'bg-red-100 text-red-800',
        ];
        $badgeCss = $colours[$colour] ?? 'bg-gray-100 text-gray-700';
    @endphp

    {{-- Application header card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm px-6 py-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ $application->programme?->name ?? 'Programme' }}
                </h2>
                @if ($application->submitted_at)
                    <p class="mt-1 text-sm text-gray-500">
                        Submitted {{ $application->submitted_at->format('d M Y') }}
                    </p>
                @endif
            </div>
            <span class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $badgeCss }}">
                {{ $application->status->label() }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left column: Status history + Documents --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Status history timeline --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Application Timeline</h3>
                </div>
                <div class="px-5 py-4">
                    @if ($history->isEmpty())
                        <p class="text-sm text-gray-400">No status changes recorded yet.</p>
                    @else
                        <ol class="relative border-l border-gray-200 space-y-4 ml-2">
                            @foreach ($history as $event)
                                <li class="ml-4">
                                    <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white portal-btn-primary"></div>
                                    <p class="text-xs text-gray-400">
                                        {{ $event->created_at->format('d M Y, g:i A') }}
                                    </p>
                                    <p class="text-sm font-medium text-gray-700">
                                        @if ($event->from_status)
                                            {{ ucwords(str_replace('_', ' ', $event->from_status)) }}
                                            <span class="text-gray-400">→</span>
                                        @endif
                                        {{ ucwords(str_replace('_', ' ', $event->to_status)) }}
                                    </p>
                                    @if ($event->reason)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $event->reason }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>

            {{-- Document checklist --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Document Checklist</h3>
                </div>
                <div class="px-5 py-4">
                    @if ($checklist === null || $checklist->items->isEmpty())
                        <p class="text-sm text-gray-400">No document checklist is assigned to this programme.</p>
                    @else
                        @php
                            $mandatory    = $checklist->items->where('is_mandatory', true);
                            $optional     = $checklist->items->where('is_mandatory', false);
                        @endphp

                        @if ($mandatory->isNotEmpty())
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                Required documents
                            </p>
                            <ul class="space-y-2 mb-4">
                                @foreach ($mandatory as $docItem)
                                    @php $submitted = $uploaded_ids->contains($docItem->id); @endphp
                                    <li class="flex items-center gap-3">
                                        @if ($submitted)
                                            <svg class="h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            <span class="text-sm text-gray-600 line-through">{{ $docItem->name }}</span>
                                        @else
                                            <svg class="h-4 w-4 shrink-0 text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                            </svg>
                                            <span class="text-sm text-gray-800 font-medium">{{ $docItem->name }}</span>
                                            <span class="text-xs text-orange-500">Pending</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($optional->isNotEmpty())
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                Optional documents
                            </p>
                            <ul class="space-y-2">
                                @foreach ($optional as $docItem)
                                    @php $submitted = $uploaded_ids->contains($docItem->id); @endphp
                                    <li class="flex items-center gap-3">
                                        @if ($submitted)
                                            <svg class="h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            <span class="text-sm text-gray-600 line-through">{{ $docItem->name }}</span>
                                        @else
                                            <svg class="h-4 w-4 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            <span class="text-sm text-gray-500">{{ $docItem->name }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>
            </div>

        </div>

        {{-- Right column: Payments + Offer letter + Downloads --}}
        <div class="space-y-6">

            {{-- Offer letter --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Offer Letter</h3>
                </div>
                <div class="px-5 py-4">
                    @if ($application->currentOfferLetter)
                        <p class="text-sm font-medium portal-text-primary mb-1">Available</p>
                        <p class="text-xs text-gray-500 mb-3">
                            Status: {{ ucfirst($application->currentOfferLetter->status) }}
                        </p>
                        <a href="{{ route('portal.downloads.offer-letter', $application->uuid) }}"
                           class="inline-flex items-center gap-1.5 rounded-md portal-btn-primary px-3 py-2 text-xs font-medium transition-opacity">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Download offer letter
                        </a>
                        @if (in_array($application->currentOfferLetter->status, ['accepted']))
                            <a href="{{ route('portal.downloads.admission-letter', $application->uuid) }}"
                               class="mt-2 inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                <svg class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Download admission letter
                            </a>
                        @endif
                    @else
                        <p class="text-sm text-gray-400">Not issued yet.</p>
                        <p class="mt-1 text-xs text-gray-400">You will be notified when your offer letter is ready.</p>
                    @endif
                </div>
            </div>

            {{-- ERP portal transition (SP-007) — shown only when enrolled --}}
            @if ($application->status->value === 'enrolled')
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-emerald-100">
                        <h3 class="text-sm font-semibold text-emerald-800">Student Portal Access</h3>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-emerald-700 mb-3">
                            You are enrolled. Access your student portal with a single click — no password required.
                        </p>
                        @if (session('info'))
                            <p class="mb-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                                {{ session('info') }}
                            </p>
                        @endif
                        <a href="{{ route('portal.applications.erp-transition', $application->uuid) }}"
                           class="inline-flex items-center gap-2 rounded-md portal-btn-primary px-4 py-2 text-sm font-medium transition-opacity">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            Go to Student Portal
                        </a>
                    </div>
                </div>
            @endif

            {{-- Payment history --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Payments</h3>
                </div>
                <div class="px-5 py-4">
                    @if ($payments->isEmpty())
                        <p class="text-sm text-gray-400">No confirmed payments yet.</p>
                    @else
                        <p class="text-xs text-gray-500 mb-3">
                            Total confirmed:
                            <span class="font-semibold text-gray-900">₹{{ number_format($total_paid, 2) }}</span>
                        </p>
                        <ul class="space-y-3">
                            @foreach ($payments as $txn)
                                <li class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800">
                                            ₹{{ number_format($txn->amount, 2) }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ $txn->created_at->format('d M Y') }}
                                            @if ($txn->description ?? null)
                                                · {{ $txn->description }}
                                            @endif
                                        </p>
                                    </div>
                                    <a href="{{ route('portal.downloads.payment-receipt', $txn->uuid) }}"
                                       class="shrink-0 text-xs portal-text-primary hover:opacity-75 transition-opacity font-medium">
                                        Receipt
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

        </div>
    </div>

</x-layouts.portal-app>
