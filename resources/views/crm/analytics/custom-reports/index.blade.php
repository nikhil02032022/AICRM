<x-layouts.crm title="Custom Reports">
    <div class="space-y-6">
        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Custom Reports</h1>
                <p class="mt-1 text-sm text-gray-500">Build, run, and export custom data reports across CRM entities</p>
            </div>
            @can('crm.reports.manage')
            <a href="{{ route('crm.reports.custom.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Report
            </a>
            @endcan
        </div>

        {{-- Reports table --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Report Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Entity</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fields</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Run</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($reports as $report)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <a href="{{ route('crm.reports.custom.show', $report->uuid) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                {{ $report->name }}
                            </a>
                            @if($report->description)
                            <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($report->description, 60) }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ $report->entity->value }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ count($report->selected_fields) }} columns</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $report->last_run_at ? $report->last_run_at->diffForHumans() : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('crm.reports.custom.show', $report->uuid) }}?run=1"
                                   class="text-sm font-medium text-green-600 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 rounded"
                                >Run</a>
                                @can('crm.reports.manage')
                                <a href="{{ route('crm.reports.custom.edit', $report->uuid) }}"
                                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                >Edit</a>
                                <form method="POST" action="{{ route('crm.reports.custom.destroy', $report->uuid) }}" onsubmit="return confirm('Delete this report?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-700">Delete</button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">
                            No custom reports yet. <a href="{{ route('crm.reports.custom.create') }}" class="text-indigo-600 hover:underline">Create your first report</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($reports->hasPages())
        <div class="flex justify-end">{{ $reports->links() }}</div>
        @endif
    </div>
</x-layouts.crm>
