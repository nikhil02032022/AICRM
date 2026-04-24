<x-layouts.crm title="Data Access Request">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Access Request</h1>
                <p class="mt-1 text-sm text-gray-500">DPDP Act 2023 — personal data access request detail (CR-004)</p>
            </div>
            <a href="{{ route('crm.compliance.data-access.index') }}" class="btn-secondary">
                &larr; Back to Requests
            </a>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Detail Card --}}
        <div class="card p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Request Information</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lead</dt>
                    <dd class="mt-1 text-sm font-medium">
                        <a href="{{ route('crm.leads.show', $request->lead) }}"
                            class="text-indigo-600 hover:text-indigo-800 hover:underline">
                            {{ $request->lead?->full_name ?? 'Lead #'.$request->lead_id }}
                        </a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</dt>
                    <dd class="mt-1">
                        @php
                            $statusBadge = match(strtolower($request->status?->value ?? '')) {
                                'pending'    => 'badge-yellow',
                                'processing' => 'badge-blue',
                                'delivered'  => 'badge-green',
                                'failed'     => 'badge-red',
                                default      => 'badge-gray',
                            };
                        @endphp
                        <span class="{{ $statusBadge }}">{{ $request->status?->label() ?? ucfirst($request->status?->value ?? '—') }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Requested At</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $request->requested_at ? $request->requested_at->format('d M Y, H:i:s') : ($request->created_at?->format('d M Y, H:i:s') ?? '—') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Delivery Method</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $request->delivery_method ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Processed At</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $request->processed_at ? $request->processed_at->format('d M Y, H:i:s') : '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Process Button --}}
        @if(strtolower($request->status?->value ?? '') === 'pending')
            <div class="card p-5">
                <h2 class="text-base font-semibold text-gray-800 mb-3">Process Request</h2>
                <p class="text-sm text-gray-600 mb-4">Compile and deliver the lead's personal data via the selected delivery method.</p>
                <form method="POST" action="{{ route('crm.compliance.data-access.process', $request) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Process this data access request and deliver data to the lead?')"
                        class="btn-primary">
                        Process &amp; Deliver Data
                    </button>
                </form>
            </div>
        @endif

        {{-- Compiled Data Preview --}}
        @if(isset($compiled) && $compiled)
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Compiled Data Preview</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Personal data compiled by DataAccessService for this lead</p>
                </div>
                <div class="p-5">
                    <pre class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs font-mono text-gray-800 overflow-x-auto max-h-96 overflow-y-auto leading-relaxed">{{ json_encode($compiled, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif

    </div>
</x-layouts.crm>
