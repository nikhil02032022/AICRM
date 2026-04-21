{{-- BRD: CRM-FM-006 — Scholarship & waiver categories --}}
<x-layouts.crm title="Scholarship Categories">
    <div class="space-y-4" x-data="{ creating: false }">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Scholarships & Waivers</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Configure merit, sports, management quota, early-bird, sibling and custom waiver categories.
                </p>
            </div>
            <button type="button" @click="creating = !creating" class="btn-primary-sm inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-text="creating ? 'Cancel' : 'New Category'"></span>
            </button>
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        <div x-show="creating" x-cloak class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-gray-900">Add Category</h3>
            <form method="POST" action="{{ route('crm.scholarships.categories.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="code" class="block text-xs font-medium text-gray-700 mb-1">Code *</label>
                        <input id="code" name="code" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="name" class="block text-xs font-medium text-gray-700 mb-1">Name *</label>
                        <input id="name" name="name" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="type" class="block text-xs font-medium text-gray-700 mb-1">Type *</label>
                        <select id="type" name="type" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @foreach (\App\Enums\CRM\Scholarships\ScholarshipType::cases() as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="programme_id" class="block text-xs font-medium text-gray-700 mb-1">Programme</label>
                        <select id="programme_id" name="programme_id" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">All programmes</option>
                            @foreach ($programmes as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="computation" class="block text-xs font-medium text-gray-700 mb-1">Computation *</label>
                        <select id="computation" name="computation" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="percent">Percent</option>
                            <option value="flat">Flat</option>
                        </select>
                    </div>
                    <div>
                        <label for="value" class="block text-xs font-medium text-gray-700 mb-1">Value *</label>
                        <input id="value" name="value" type="number" step="0.01" min="0" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="max_cap" class="block text-xs font-medium text-gray-700 mb-1">Max Cap</label>
                        <input id="max_cap" name="max_cap" type="number" step="0.01" min="0" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
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
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Programme</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Value</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($items as $item)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-mono text-gray-700">{{ $item->code }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                        {{ $item->type?->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->programme?->name ?? 'All' }}</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900">
                                    {{ $item->computation === 'percent' ? number_format((float) $item->value, 2).'%' : number_format((float) $item->value, 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($item->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('crm.scholarships.categories.toggle', $item) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-secondary-sm">Toggle</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-16 text-center text-sm text-gray-500">No categories configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())
                <div class="border-t border-gray-200 px-6 py-3">{{ $items->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.crm>
