<x-layouts.crm :title="'IVR Config — ' . $ivrConfig->provider->label()">
    <div class="max-w-2xl space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('crm.settings.ivr.index') }}"
                   class="mb-2 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to IVR Configurations
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $ivrConfig->provider->label() }} IVR Config</h1>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('crm.settings.ivr.toggle', $ivrConfig->uuid) }}">
                    @csrf
                    <button type="submit"
                        @class(['badge cursor-pointer text-xs px-3 py-1', 'badge-green' => $ivrConfig->is_active, 'badge-red' => !$ivrConfig->is_active])
                        title="{{ $ivrConfig->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                        {{ $ivrConfig->is_active ? 'Active' : 'Inactive' }}
                    </button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        {{-- Config Details --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">

            <div class="grid grid-cols-2 gap-4 text-sm">

                <div>
                    <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Provider</span>
                    <span class="mt-1 block font-medium text-gray-900">{{ $ivrConfig->provider->label() }}</span>
                </div>

                <div>
                    <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Status</span>
                    <span @class(['mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', 'bg-green-100 text-green-800' => $ivrConfig->is_active, 'bg-red-100 text-red-800' => !$ivrConfig->is_active])>
                        {{ $ivrConfig->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Virtual Number</span>
                    <span class="mt-1 block font-mono text-gray-900">••• (encrypted)</span>
                </div>

                <div>
                    <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Fallback Counsellor</span>
                    <span class="mt-1 block text-gray-900">{{ $ivrConfig->fallbackCounsellor?->name ?? '—' }}</span>
                </div>

                <div>
                    <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Collect Name</span>
                    <span class="mt-1 block text-gray-900">{{ $ivrConfig->collect_name ? 'Yes' : 'No' }}</span>
                </div>

                <div>
                    <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Collect Programme</span>
                    <span class="mt-1 block text-gray-900">{{ $ivrConfig->collect_programme ? 'Yes' : 'No' }}</span>
                </div>

            </div>

            <div>
                <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Welcome Message</span>
                <p class="mt-1 text-sm text-gray-700 rounded-lg bg-gray-50 p-3 border border-gray-200">{{ $ivrConfig->welcome_message }}</p>
            </div>

        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            @can('crm.settings.manage')
            <a href="{{ route('crm.settings.ivr.edit', $ivrConfig->uuid) }}" class="btn-primary">Edit Configuration</a>
            {{-- Hidden delete form --}}
            <form id="form-del-ivr-show" method="POST" action="{{ route('crm.settings.ivr.destroy', $ivrConfig->uuid) }}" class="hidden">
                @csrf @method('DELETE')
            </form>
            <button type="button"
                    @click="$dispatch('confirm-delete', { formId: 'form-del-ivr-show', itemName: '{{ addslashes($ivrConfig->provider->value) }} IVR Configuration' })"
                    class="btn-danger">
                Delete
            </button>
            @endcan
            <a href="{{ route('crm.settings.ivr.index') }}" class="btn-secondary">Back to list</a>
        </div>

    </div>

    <x-crm.confirm-modal variant="delete" subtext="This IVR configuration will be permanently removed. This cannot be undone." />
</x-layouts.crm>
