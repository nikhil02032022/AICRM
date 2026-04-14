<x-layouts.crm title="Workflow Templates">
    <div class="space-y-6"
         x-data="{ importing: null }"
    >
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Workflow Templates</h1>
                <p class="mt-1 text-sm text-gray-500">Pre-built automation templates — import to create a ready-to-configure workflow</p>
            </div>
            @can('crm.settings.custom-fields.manage')
            <a href="{{ route('crm.settings.workflow-templates.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Template
            </a>
            @endcan
        </div>

        {{-- Category filter --}}
        @php $categories = \App\Enums\CRM\WorkflowTemplateCategory::cases(); @endphp
        <div class="flex gap-2 flex-wrap" x-data="{ active: '{{ request('category', '') }}' }">
            <a href="{{ route('crm.settings.workflow-templates.index') }}"
               :class="active === '' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
               class="inline-flex items-center rounded-full border border-gray-200 px-4 py-1.5 text-sm font-medium transition-colors"
               @click="active = ''"
            >All</a>
            @foreach($categories as $cat)
            <a href="{{ route('crm.settings.workflow-templates.index') }}?category={{ $cat->value }}"
               :class="active === '{{ $cat->value }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-gray-50'"
               class="inline-flex items-center rounded-full border border-gray-200 px-4 py-1.5 text-sm font-medium transition-colors capitalize"
               @click="active = '{{ $cat->value }}'"
            >{{ str_replace('_', ' ', $cat->value) }}</a>
            @endforeach
        </div>

        {{-- Template gallery grid --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($templates as $template)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5 flex flex-col gap-4 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700 capitalize mb-2">
                            {{ str_replace('_', ' ', $template->category->value) }}
                        </span>
                        <h3 class="text-sm font-semibold text-gray-900">{{ $template->name }}</h3>
                        @if($template->description)
                        <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ $template->description }}</p>
                        @endif
                    </div>
                    @if($template->is_global)
                    <span class="ml-2 flex-shrink-0 inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600">Global</span>
                    @endif
                </div>
                <div class="flex items-center justify-between mt-auto">
                    <span class="text-xs text-gray-400">Used {{ $template->used_count }} {{ Str::plural('time', $template->used_count) }}</span>
                    <div class="flex items-center gap-2">
                        @can('crm.settings.custom-fields.manage')
                        <button
                            type="button"
                            :disabled="importing === '{{ $template->uuid }}'"
                            @click="
                                importing = '{{ $template->uuid }}';
                                fetch('{{ route('crm.settings.workflow-templates.import', $template->uuid) }}', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                })
                                .then(r => r.json())
                                .then(json => {
                                    if (json.success && json.data && json.data.uuid) {
                                        window.location = '/crm/automation/workflows/' + json.data.uuid + '/edit';
                                    } else {
                                        alert('Import failed.');
                                    }
                                })
                                .finally(() => importing = null)
                            "
                            class="btn-primary text-xs disabled:opacity-50"
                            aria-label="Import {{ $template->name }}"
                        >
                            <span :class="importing === '{{ $template->uuid }}' ? 'hidden' : ''">Import</span>
                            <span :class="importing === '{{ $template->uuid }}' ? '' : 'hidden'">Importing…</span>
                        </button>
                        <a href="{{ route('crm.settings.workflow-templates.edit', $template->uuid) }}"
                           class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                        @endcan
                    </div>
                </div>
            </div>
            @empty
            <div class="sm:col-span-2 lg:col-span-3 py-16 text-center text-sm text-gray-400">
                No workflow templates found.
                @can('crm.settings.custom-fields.manage')
                <a href="{{ route('crm.settings.workflow-templates.create') }}" class="text-indigo-600 hover:underline ml-1">Create one</a>.
                @endcan
            </div>
            @endforelse
        </div>

        @if($templates->hasPages())
        <div class="flex justify-end">{{ $templates->links() }}</div>
        @endif
    </div>
</x-layouts.crm>
