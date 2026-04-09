<x-layouts.crm>
    <x-slot:header>Web Forms</x-slot:header>

    <x-slot:headerActions>
        @can('crm.forms.create')
        <a href="{{ route('crm.forms.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Form
        </a>
        @endcan
    </x-slot:headerActions>

    {{-- Filter bar --}}
    <div class="mb-6" x-data="{ status: '{{ request('is_active', '') }}' }">
        <form method="GET" action="{{ route('crm.forms.index') }}" class="flex flex-wrap items-center gap-3">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search forms…"
                class="w-64 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                aria-label="Search web forms"
            >
            <select name="is_active"
                    x-model="status"
                    @change="$el.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                    aria-label="Filter by status">
                <option value="">All Statuses</option>
                <option value="1" @selected(request('is_active') === '1')>Active</option>
                <option value="0" @selected(request('is_active') === '0')>Inactive</option>
            </select>
            <button type="submit"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Search
            </button>
            @if(request()->hasAny(['search','is_active']))
            <a href="{{ route('crm.forms.index') }}"
               class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline">Clear filters</a>
            @endif
        </form>
    </div>

    {{-- Forms table --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        @if($forms->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="mb-4 h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                </svg>
                <p class="text-base font-semibold text-gray-700">No web forms yet</p>
                <p class="mt-1 text-sm text-gray-500">Create your first web enquiry form to start capturing leads.</p>
                @can('crm.forms.create')
                <a href="{{ route('crm.forms.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    New Form
                </a>
                @endcan
            </div>
        @else
        <table class="min-w-full divide-y divide-gray-200" role="table">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Form Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Slug / URL</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Source</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Created</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach($forms as $form)
                <tr class="transition-colors hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="text-sm font-semibold text-gray-900">{{ $form->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">v{{ $form->consent_form_version }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 font-mono">/f/{{ $form->slug }}</code>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">
                            {{ $form->source?->label() ?? 'Website' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($form->is_active)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400" aria-hidden="true"></span>
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $form->created_at?->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            {{-- Embed Code + QR --}}
                            <a href="{{ route('crm.forms.embed-code', $form->uuid) }}"
                               class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               title="Embed code & QR">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/>
                                </svg>
                                Embed
                            </a>
                            {{-- Edit --}}
                            @can('crm.forms.edit')
                            <a href="{{ route('crm.forms.edit', $form->uuid) }}"
                               class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               title="Edit form">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                </svg>
                                Edit
                            </a>
                            @endcan
                            {{-- Preview --}}
                            <a href="{{ route('crm.forms.preview', $form->uuid) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               title="Preview form">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                                Preview
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($forms->hasPages())
        <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
            {{ $forms->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>
</x-layouts.crm>
