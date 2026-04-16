<x-layouts.crm>
    <x-slot:header>Application Form Builder</x-slot:header>

    <x-slot:headerActions>
        <div class="flex items-center gap-2">
            <a href="{{ route('crm.applications.programmes.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Programme Catalogue
            </a>
            <a href="{{ route('crm.applications.forms.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                New Template
            </a>
        </div>
    </x-slot:headerActions>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Template</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Version</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Updated</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($templates as $template)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="text-sm font-semibold text-gray-900">{{ $template->name }}</p>
                                <p class="text-xs text-gray-500">/{{ $template->slug }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700">v{{ $template->version }}</td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-green-100 text-green-700' => $template->is_active,
                                    'bg-gray-100 text-gray-700' => !$template->is_active,
                                ])>
                                    {{ $template->is_active ? 'Active' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $template->updated_at?->diffForHumans() }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('crm.applications.forms.fill', $template->uuid) }}"
                                       class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                        Fill Form
                                    </a>
                                    <a href="{{ route('crm.applications.forms.edit', $template->uuid) }}"
                                       class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">No application form templates yet.</p>
                                <a href="{{ route('crm.applications.forms.create') }}" class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">Create your first template</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3">
            {{ $templates->links() }}
        </div>
    </div>
</x-layouts.crm>
