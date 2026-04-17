<x-layouts.crm>
    <x-slot:header>ERP Conversions</x-slot:header>

    <div class="space-y-4">

        {{-- Filters --}}
        <form method="GET" action="{{ route('crm.conversions.index') }}" class="flex flex-wrap gap-3">
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                @foreach(['pending','success','failed'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500">
            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                Filter
            </button>
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Application UUID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Lead</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">ERP Student ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Attempted</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500">Retries</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ substr($log->application_uuid, 0, 8) }}…</td>
                        <td class="px-4 py-3 text-gray-800">{{ $log->lead?->fullName() ?? substr($log->lead_uuid ?? '', 0, 8) }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $log->erp_student_id ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $colour = match($log->status) {
                                    'success' => 'bg-green-100 text-green-700',
                                    'failed'  => 'bg-red-100 text-red-700',
                                    default   => 'bg-yellow-100 text-yellow-700',
                                };
                            @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $colour }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $log->attempted_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $log->retry_count }}</td>
                        <td class="px-4 py-3 flex items-center gap-3">
                            <a href="{{ route('crm.conversions.show', $log->uuid) }}"
                               class="text-indigo-600 hover:underline text-xs font-medium">View</a>
                            @if($log->isEligibleForRetry())
                                <form method="POST" action="{{ route('crm.conversions.retry', $log->uuid) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-orange-600 hover:underline text-xs font-medium">Retry</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">No conversion logs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $logs->withQueryString()->links() }}</div>

    </div>
</x-layouts.crm>
