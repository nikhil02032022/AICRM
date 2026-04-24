<x-layouts.crm title="Academic Years">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Academic Years</h1>
                <p class="mt-1 text-sm text-gray-500">Manage academic year periods and lifecycle</p>
            </div>
            <a href="{{ route('crm.admin.academic-years.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Academic Year
            </a>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Table --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Label</th>
                            <th class="table-th">Start Date</th>
                            <th class="table-th">End Date</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Active</th>
                            <th class="table-th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse($years as $year)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="table-td font-medium text-gray-900">{{ $year->label }}</td>
                                <td class="table-td text-gray-600">{{ $year->start_date->format('d M Y') }}</td>
                                <td class="table-td text-gray-600">{{ $year->end_date->format('d M Y') }}</td>
                                <td class="table-td">
                                    <span class="{{ $year->status->badgeClass() }}">{{ $year->status->label() }}</span>
                                </td>
                                <td class="table-td">
                                    @if($year->is_active)
                                        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="Active" title="Active">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="table-td text-right">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        {{-- Edit --}}
                                        <a
                                            href="{{ route('crm.admin.academic-years.edit', $year) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>

                                        {{-- Activate --}}
                                        @unless($year->is_active)
                                            <form
                                                method="POST"
                                                action="{{ route('crm.admin.academic-years.activate', $year) }}"
                                                onsubmit="return confirm('Set \'{{ addslashes($year->label) }}\' as the active academic year?')"
                                            >
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 shadow-sm hover:bg-indigo-100 transition-colors"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                    </svg>
                                                    Activate
                                                </button>
                                            </form>
                                        @endunless

                                        {{-- Rollover --}}
                                        <form
                                            method="POST"
                                            action="{{ route('crm.admin.academic-years.rollover', $year) }}"
                                            onsubmit="return confirm('Roll over \'{{ addslashes($year->label) }}\' to a new academic year? This will copy intake structures.')"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 shadow-sm hover:bg-amber-100 transition-colors"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Rollover
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-td text-center text-gray-400 py-10">
                                    No academic years found.
                                    <a href="{{ route('crm.admin.academic-years.create') }}" class="text-indigo-600 hover:underline ml-1">Create the first one.</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($years->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">
                    {{ $years->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.crm>
