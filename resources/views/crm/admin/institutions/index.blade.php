<x-layouts.crm title="Institutions">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Institutions</h1>
                <p class="mt-1 text-sm text-gray-500">Manage institution profiles and settings</p>
            </div>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Search bar --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('crm.admin.institutions.index') }}" class="flex items-center gap-3">
                <div class="flex-1">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name, city or country…"
                        class="form-input"
                    >
                </div>
                <button type="submit" class="btn-primary">Search</button>
                @if(request('search'))
                    <a href="{{ route('crm.admin.institutions.index') }}" class="btn-secondary">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Name</th>
                            <th class="table-th">City</th>
                            <th class="table-th">Country</th>
                            <th class="table-th">Timezone</th>
                            <th class="table-th">Created</th>
                            <th class="table-th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse($institutions as $institution)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="table-td font-medium text-gray-900">{{ $institution->name }}</td>
                                <td class="table-td text-gray-600">{{ $institution->city ?? '—' }}</td>
                                <td class="table-td text-gray-600">{{ $institution->country ?? '—' }}</td>
                                <td class="table-td text-gray-600">{{ $institution->timezone ?? '—' }}</td>
                                <td class="table-td text-gray-500 text-sm">{{ $institution->created_at->format('d M Y') }}</td>
                                <td class="table-td text-right">
                                    <a
                                        href="{{ route('crm.admin.institutions.edit', $institution) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-td text-center text-gray-400 py-10">
                                    No institutions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($institutions->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">
                    {{ $institutions->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.crm>
