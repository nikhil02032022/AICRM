{{-- BRD: CRM-DM-001 — Programme-wise document checklists --}}
<x-layouts.crm title="Document Checklists">
    <div class="space-y-4" x-data="{ creating: false }">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Document Checklists</h2>
                <p class="mt-1 text-sm text-gray-600">Define the documents each programme requires from applicants.</p>
            </div>
            <button type="button" @click="creating = !creating" class="btn-primary-sm inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-text="creating ? 'Cancel' : 'New Checklist'"></span>
            </button>
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div x-show="creating" x-cloak class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-gray-900">Add Checklist</h3>
            <form method="POST" action="{{ route('crm.documents.checklists.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-xs font-medium text-gray-700 mb-1">Name *</label>
                        <input id="name" name="name" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="programme_id" class="block text-xs font-medium text-gray-700 mb-1">Programme</label>
                        <select id="programme_id" name="programme_id" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">Institution default</option>
                            @foreach ($programmes as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="creating = false" class="btn-ghost-sm">Cancel</button>
                    <button type="submit" class="btn-primary-sm">Save</button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Programme</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($checklists as $cl)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $cl->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $cl->programme?->name ?? 'Institution default' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $cl->items->count() }}</td>
                                <td class="px-6 py-4">
                                    @if ($cl->is_active)
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('crm.documents.checklists.toggle', $cl) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-secondary-sm">Toggle</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-16 text-center text-sm text-gray-500">No checklists configured yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($checklists->hasPages())
                <div class="border-t border-gray-200 px-6 py-3">{{ $checklists->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.crm>
