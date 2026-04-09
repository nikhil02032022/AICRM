<x-layouts.crm title="Integrations">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Channel Integrations</h1>
                <p class="mt-1 text-sm text-gray-500">Manage webhook credentials for Google Ads, Meta, and education portals</p>
            </div>
            @can('crm.integrations.manage')
            <a href="{{ route('crm.settings.integrations.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Integration
            </a>
            @endcan
        </div>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800" role="alert">
            <svg class="h-5 w-5 flex-shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        @forelse($credentials as $credential)
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" x-data="{ confirmDelete: false }">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <span class="text-base font-semibold text-gray-900">{{ $credential->label }}</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $credential->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                            {{ $credential->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ $credential->channel->label() }}</p>
                    @if($credential->last_used_at)
                    <p class="mt-0.5 text-xs text-gray-400">Last webhook received {{ $credential->last_used_at->diffForHumans() }}</p>
                    @else
                    <p class="mt-0.5 text-xs text-gray-400">No webhook received yet</p>
                    @endif

                    {{-- Webhook URL --}}
                    @php
                        $webhookUrl = match($credential->channel->value) {
                            'google_ads'    => url("/api/v1/crm/webhooks/google/{$credential->uuid}"),
                            'meta'          => url("/api/v1/crm/webhooks/meta/{$credential->uuid}"),
                            default         => url("/api/v1/crm/webhooks/portal/{$credential->channel->value}/{$credential->uuid}"),
                        };
                    @endphp
                    <div class="mt-3 flex items-center gap-2 rounded-lg bg-gray-50 border border-gray-200 px-3 py-2"
                         x-data="{ copied: false }">
                        <code class="flex-1 truncate font-mono text-xs text-gray-700">{{ $webhookUrl }}</code>
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                aria-label="Copy webhook URL">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" class="text-green-600">Copied!</span>
                        </button>
                    </div>
                </div>

                {{-- Actions --}}
                @can('crm.integrations.manage')
                <div class="flex items-center gap-2">
                    <a href="{{ route('crm.settings.integrations.edit', $credential->uuid) }}"
                       class="btn-secondary text-xs">Edit</a>

                    <div x-show="!confirmDelete">
                        <button type="button" @click="confirmDelete = true"
                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 transition-colors">
                            Remove
                        </button>
                    </div>
                    <div x-show="confirmDelete" x-transition class="flex items-center gap-2" style="display:none">
                        <span class="text-xs text-gray-600">Remove this integration?</span>
                        <form method="POST" action="{{ route('crm.settings.integrations.destroy', $credential->uuid) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition-colors">
                                Confirm
                            </button>
                        </form>
                        <button type="button" @click="confirmDelete = false"
                                class="text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                    </div>
                </div>
                @endcan
            </div>
        </div>
        @empty
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
            </svg>
            <p class="mt-3 text-sm font-medium text-gray-500">No integrations configured</p>
            <p class="mt-1 text-xs text-gray-400">Add a Google Ads, Meta, or education portal integration to start auto-importing leads.</p>
            @can('crm.integrations.manage')
            <a href="{{ route('crm.settings.integrations.create') }}"
               class="mt-4 inline-block btn-primary text-sm">Add First Integration</a>
            @endcan
        </div>
        @endforelse

    </div>
</x-layouts.crm>
