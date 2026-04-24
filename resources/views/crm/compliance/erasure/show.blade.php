<x-layouts.crm title="PII Erasure Request">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">PII Erasure Request</h1>
                <p class="mt-1 text-sm text-gray-500">DPDP Act 2023 — right to erasure (CR-005)</p>
            </div>
            <a href="{{ route('crm.compliance.erasure.index') }}" class="btn-secondary">
                &larr; Back to Erasure Requests
            </a>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Detail Card --}}
        <div class="card p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Erasure Request Details</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lead</dt>
                    <dd class="mt-1 text-sm font-medium">
                        @if($erasureRequest->lead)
                            <a href="{{ route('crm.leads.show', $erasureRequest->lead) }}"
                                class="text-indigo-600 hover:text-indigo-800 hover:underline">
                                {{ $erasureRequest->lead->full_name }}
                            </a>
                        @else
                            <span class="text-gray-500 italic">Lead #{{ $erasureRequest->lead_id }} (data erased)</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</dt>
                    <dd class="mt-1">
                        @php
                            $statusBadge = match(strtolower($erasureRequest->status?->value ?? '')) {
                                'pending'   => 'badge-yellow',
                                'scheduled' => 'badge-blue',
                                'erased'    => 'badge-green',
                                'failed'    => 'badge-red',
                                default     => 'badge-gray',
                            };
                        @endphp
                        <span class="{{ $statusBadge }}">{{ $erasureRequest->status?->label() ?? ucfirst($erasureRequest->status?->value ?? '—') }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Requested At</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $erasureRequest->requested_at ? $erasureRequest->requested_at->format('d M Y, H:i:s') : ($erasureRequest->created_at?->format('d M Y, H:i:s') ?? '—') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Scheduled Erasure At</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $erasureRequest->scheduled_erasure_at ? $erasureRequest->scheduled_erasure_at->format('d M Y, H:i:s') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Erased At</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $erasureRequest->erased_at ? $erasureRequest->erased_at->format('d M Y, H:i:s') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Erased By Job</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $erasureRequest->erased_by_job ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Schedule Erasure Action --}}
        @if(strtolower($erasureRequest->status?->value ?? '') === 'pending')
            <div class="card p-5">
                <h2 class="text-base font-semibold text-gray-800 mb-2">Schedule Erasure</h2>

                {{-- Info Note --}}
                <div class="rounded-lg bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-sm mb-4 flex items-start gap-2">
                    <svg class="h-4 w-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Erasure will be scheduled for 30 days from today. The lead's PII will be permanently anonymised after this date.</span>
                </div>

                <form method="POST" action="{{ route('crm.compliance.erasure.schedule', $erasureRequest) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Schedule erasure for 30 days from today? This cannot be undone.')"
                        class="btn-primary">
                        Schedule Erasure
                    </button>
                </form>
            </div>
        @endif

    </div>
</x-layouts.crm>
