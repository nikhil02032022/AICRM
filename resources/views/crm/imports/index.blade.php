<x-layouts.crm title="Lead Imports">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Lead Imports</h1>
                <p class="mt-1 text-sm text-gray-500">Bulk CSV/Excel uploads and digital channel import history</p>
            </div>
            @can('crm.leads.import')
            <a href="{{ route('crm.imports.upload') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Upload CSV / Excel
            </a>
            @endcan
        </div>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800" role="alert">
            <svg class="h-5 w-5 flex-shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('crm.imports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="filter-channel" class="mb-1 block text-xs font-medium text-gray-600">Channel</label>
                <select id="filter-channel" name="channel"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">All channels</option>
                    @foreach($channelOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('channel') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-status" class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                <select id="filter-status" name="status"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-secondary">Filter</button>
            @if(request()->hasAny(['channel', 'status']))
            <a href="{{ route('crm.imports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>

        {{-- Batches table --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">File / Batch</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Channel</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Success</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Failed</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Initiated by</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($batches as $batch)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-900">{{ $batch->file_name ?? 'Webhook batch' }}</span>
                            <span class="ml-1 font-mono text-xs text-gray-400">{{ substr($batch->uuid, 0, 8) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $batch->channel->label() }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $batch->status->colour() }}">
                                {{ $batch->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($batch->total_rows) }}</td>
                        <td class="px-4 py-3 text-right text-green-700 font-medium">
                            {{ number_format($batch->processed_rows - $batch->failed_rows) }}
                        </td>
                        <td class="px-4 py-3 text-right {{ $batch->failed_rows > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                            {{ number_format($batch->failed_rows) }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $batch->initiatedBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $batch->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($batch->error_report_path)
                            <a href="{{ route('crm.imports.report', $batch->uuid) }}"
                               class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Download errors
                            </a>
                            @else
                            <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                            No import batches found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($batches->hasPages())
        <div class="mt-4">
            {{ $batches->links() }}
        </div>
        @endif

    </div>
</x-layouts.crm>
