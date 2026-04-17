<x-layouts.crm>
    <x-slot:header>Conversion Log Detail</x-slot:header>

    <div class="max-w-3xl space-y-5">

        <a href="{{ route('crm.conversions.index') }}"
           class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
            &larr; All Conversions
        </a>

        @php
            $colour = match($log->status) {
                'success' => 'bg-green-100 text-green-700',
                'failed'  => 'bg-red-100 text-red-700',
                default   => 'bg-yellow-100 text-yellow-700',
            };
        @endphp

        {{-- Summary card --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm space-y-4 text-sm">
            <div class="flex flex-wrap gap-8">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Status</p>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colour }}">
                        {{ ucfirst($log->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">ERP Student ID</p>
                    <p class="font-mono text-gray-800">{{ $log->erp_student_id ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Retry Count</p>
                    <p class="text-gray-800">{{ $log->retry_count }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-8">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Application UUID</p>
                    <p class="font-mono text-xs text-gray-600">{{ $log->application_uuid }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Lead UUID</p>
                    <p class="font-mono text-xs text-gray-600">{{ $log->lead_uuid }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-8">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Attempted At</p>
                    <p class="text-gray-800">{{ $log->attempted_at?->format('d M Y H:i:s') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Completed At</p>
                    <p class="text-gray-800">{{ $log->completed_at?->format('d M Y H:i:s') ?? '—' }}</p>
                </div>
                @if($log->next_retry_at)
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Next Retry At</p>
                    <p class="text-gray-800">{{ $log->next_retry_at->format('d M Y H:i:s') }}</p>
                </div>
                @endif
            </div>

            @if($log->converted_by_user_id)
            <div>
                <p class="text-xs font-medium text-gray-500 mb-1">Initiated By</p>
                <p class="text-gray-800">{{ $log->convertedBy?->name ?? $log->converted_by_user_id }}</p>
            </div>
            @endif

            @if($log->error_message)
            <div>
                <p class="text-xs font-medium text-gray-500 mb-1">Error</p>
                <p class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ $log->error_message }}</p>
            </div>
            @endif
        </div>

        {{-- Payload --}}
        @if($log->conversion_payload)
        <details class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-gray-600">
                Conversion Payload
            </summary>
            <pre class="border-t border-gray-100 p-4 text-xs text-gray-700 overflow-x-auto">{{ json_encode($log->conversion_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
        @endif

        {{-- ERP Response --}}
        @if($log->erp_response)
        <details class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-gray-600">
                ERP Response
            </summary>
            <pre class="border-t border-gray-100 p-4 text-xs text-gray-700 overflow-x-auto">{{ json_encode($log->erp_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
        @endif

        {{-- Retry --}}
        @if($log->isEligibleForRetry())
        <form method="POST" action="{{ route('crm.conversions.retry', $log->uuid) }}">
            @csrf
            <button type="submit"
                    class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600">
                Retry ERP Conversion
            </button>
        </form>
        @endif

    </div>
</x-layouts.crm>
