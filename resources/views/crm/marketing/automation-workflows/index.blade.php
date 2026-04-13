<x-layouts.crm>
    <x-slot:header>Automation Workflows</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ route('crm.marketing.automation-workflows.create') }}"
           class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Workflow
        </a>
    </x-slot:headerActions>

    <div class="mb-6 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-sky-50 p-5 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Visual journey builder for marketing automation</h2>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
            Build trigger-condition-action sequences, keep steps ordered, and publish workflows for downstream execution in upcoming MA slices.
        </p>
    </div>

    <div class="mb-6">
        <form method="GET" action="{{ route('crm.marketing.automation-workflows.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="search"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Search automation workflows..."
                   class="w-72 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                   aria-label="Search automation workflows">
            <select name="status"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="">All statuses</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="paused" @selected(request('status') === 'paused')>Paused</option>
                <option value="archived" @selected(request('status') === 'archived')>Archived</option>
            </select>
            <button type="submit"
                    class="inline-flex min-h-11 items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Filter
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if($workflows->isEmpty())
            <div class="px-6 py-20 text-center">
                <h3 class="text-lg font-semibold text-gray-900">No automation workflows yet</h3>
                <p class="mt-2 text-sm text-gray-500">Create your first workflow to define trigger and action steps.</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Workflow</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Trigger</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Steps</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($workflows as $workflow)
                        <tr class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4 align-top">
                                <p class="text-sm font-semibold text-gray-900">{{ $workflow->name }}</p>
                                <p class="mt-1 text-xs text-gray-500">Version {{ $workflow->version }}</p>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">{{ $workflow->trigger_type }}</td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">{{ (int) $workflow->steps_count }}</td>
                            <td class="px-6 py-4 align-top">
                                @php
                                    $badgeClass = match ($workflow->status?->value) {
                                        'active' => 'bg-emerald-50 text-emerald-700',
                                        'paused' => 'bg-amber-50 text-amber-700',
                                        'archived' => 'bg-gray-100 text-gray-600',
                                        default => 'bg-indigo-50 text-indigo-700',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">{{ $workflow->status?->label() }}</span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('crm.marketing.automation-workflows.edit', $workflow->uuid) }}"
                                       class="inline-flex min-h-10 items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-200">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('crm.marketing.automation-workflows.destroy', $workflow->uuid) }}" onsubmit="return confirm('Delete this workflow?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex min-h-10 items-center rounded-md bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition-colors hover:bg-red-100">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($workflows->hasPages())
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                    {{ $workflows->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.crm>
