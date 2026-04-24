<x-layouts.crm title="Campuses">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Campuses</h1>
                <p class="mt-1 text-sm text-gray-500">Manage campuses within your institution</p>
            </div>
            <a href="{{ route('crm.admin.campuses.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Campus
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
                            <th class="table-th">Name</th>
                            <th class="table-th">Code</th>
                            <th class="table-th">City</th>
                            <th class="table-th">State</th>
                            <th class="table-th">Status</th>
                            <th class="table-th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse($campuses as $campus)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="table-td font-medium text-gray-900">{{ $campus->name }}</td>
                                <td class="table-td">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-mono font-medium text-gray-700">{{ $campus->code }}</span>
                                </td>
                                <td class="table-td text-gray-600">{{ $campus->city ?? '—' }}</td>
                                <td class="table-td text-gray-600">{{ $campus->state ?? '—' }}</td>
                                <td class="table-td">
                                    @if($campus->is_active)
                                        <span class="badge-green">Active</span>
                                    @else
                                        <span class="badge-gray">Inactive</span>
                                    @endif
                                </td>
                                <td class="table-td text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            href="{{ route('crm.admin.campuses.edit', $campus) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <form
                                            method="POST"
                                            action="{{ route('crm.admin.campuses.destroy', $campus) }}"
                                            onsubmit="return confirm('Delete campus \'{{ addslashes($campus->name) }}\'? This cannot be undone.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 shadow-sm hover:bg-red-50 transition-colors"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-td text-center text-gray-400 py-10">
                                    No campuses found.
                                    <a href="{{ route('crm.admin.campuses.create') }}" class="text-indigo-600 hover:underline ml-1">Add the first campus.</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($campuses->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">
                    {{ $campuses->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.crm>
