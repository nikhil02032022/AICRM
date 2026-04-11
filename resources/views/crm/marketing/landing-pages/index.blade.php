<x-layouts.crm>
    <x-slot:header>Landing Pages</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ route('crm.marketing.landing-pages.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Landing Page
        </a>
    </x-slot:headerActions>

    <div class="mb-6 rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-orange-50 p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-700">Marketing Capture</p>
                <h2 class="mt-1 text-xl font-semibold text-gray-900">Campaign pages linked to CRM web forms</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">
                    Publish branded landing pages, attach attribution parameters, and route visitors into the existing DPDP-compliant web form flow.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 text-center shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Total</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $landingPages->total() }}</p>
                </div>
                <div class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 text-center shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Published</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-700">{{ $landingPages->getCollection()->where('status.value', 'published')->count() }}</p>
                </div>
                <div class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 text-center shadow-sm col-span-2 sm:col-span-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Drafts</p>
                    <p class="mt-1 text-lg font-semibold text-amber-700">{{ $landingPages->getCollection()->where('status.value', 'draft')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6" x-data="{ status: '{{ request('status', '') }}' }">
        <form method="GET" action="{{ route('crm.marketing.landing-pages.index') }}" class="flex flex-wrap items-center gap-3">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search landing pages..."
                class="w-72 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                aria-label="Search landing pages"
            >
            <select name="status"
                    x-model="status"
                    @change="$el.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                    aria-label="Filter by landing page status">
                <option value="">All statuses</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="published" @selected(request('status') === 'published')>Published</option>
                <option value="archived" @selected(request('status') === 'archived')>Archived</option>
            </select>
            <button type="submit"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('crm.marketing.landing-pages.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline">Clear filters</a>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if($landingPages->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5M3.75 9.75h16.5m-16.5 4.5h9.75m-9.75 4.5h9.75M17.25 13.5l1.5 1.5 3-3"/>
                    </svg>
                </div>
                <h3 class="mt-5 text-lg font-semibold text-gray-900">No landing pages yet</h3>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-gray-500">
                    Start with a campaign-specific page and attach one of your CRM web forms to capture attributed enquiries.
                </p>
                <a href="{{ route('crm.marketing.landing-pages.create') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 cursor-pointer">
                    Create landing page
                </a>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Page</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Web Form</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Attribution</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Views</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Updated</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($landingPages as $landingPage)
                    <tr class="transition-colors hover:bg-gray-50">
                        <td class="px-6 py-4 align-top">
                            <p class="text-sm font-semibold text-gray-900">{{ $landingPage->name }}</p>
                            <p class="mt-1 text-xs text-gray-500">/lp/{{ $landingPage->slug }}</p>
                            <p class="mt-2 text-xs text-gray-500 line-clamp-2">{{ $landingPage->headline }}</p>
                        </td>
                        <td class="px-6 py-4 align-top text-sm text-gray-600">
                            @if($landingPage->webForm)
                                <p class="font-medium text-gray-900">{{ $landingPage->webForm->name }}</p>
                                <p class="mt-1 text-xs text-gray-500">/f/{{ $landingPage->webForm->slug }}</p>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">Not linked</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top">
                            @if(!empty($landingPage->attribution_params))
                                <div class="flex flex-wrap gap-2">
                                    @foreach($landingPage->attribution_params as $key => $value)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                            {{ $key }}: {{ $value }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-gray-400">No UTM parameters</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top text-sm text-gray-700">
                            <p class="font-semibold text-gray-900">{{ number_format((int) ($landingPage->landing_page_views_count ?? 0)) }}</p>
                            <p class="mt-1 text-xs text-gray-500">Last 7d: {{ number_format((int) ($landingPage->view_count_last_7d ?? 0)) }}</p>
                        </td>
                        <td class="px-6 py-4 align-top">
                            @php
                                $statusClasses = match ($landingPage->status?->value) {
                                    'published' => 'bg-emerald-50 text-emerald-700',
                                    'archived' => 'bg-gray-100 text-gray-600',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClasses }}">
                                {{ $landingPage->status?->label() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 align-top text-sm text-gray-500">{{ $landingPage->updated_at?->format('d M Y, h:i A') }}</td>
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center justify-end gap-2">
                                @if($landingPage->status?->isPubliclyVisible())
                                    <a href="{{ $landingPage->publicUrl() }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-100 cursor-pointer">
                                        Open
                                    </a>
                                @endif
                                <a href="{{ route('crm.marketing.landing-pages.edit', $landingPage->uuid) }}"
                                   class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2.5 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200 cursor-pointer">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('crm.marketing.landing-pages.destroy', $landingPage->uuid) }}" onsubmit="return confirm('Delete this landing page?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 cursor-pointer">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($landingPages->hasPages())
            <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                {{ $landingPages->withQueryString()->links() }}
            </div>
            @endif
        @endif
    </div>
</x-layouts.crm>