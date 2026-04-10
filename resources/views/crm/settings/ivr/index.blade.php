<x-layouts.crm title="IVR Configurations">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">IVR Configurations</h1>
                <p class="mt-1 text-sm text-gray-500">Inbound enquiry IVR — auto-creates leads on call · BRD CC-019</p>
            </div>
            @can('crm.settings.manage')
            <a href="{{ route('crm.settings.ivr.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add IVR Config
            </a>
            @endcan
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        <div class="card">
            <div class="card-body">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="table-th">Provider</th>
                            <th class="table-th">Campus</th>
                            <th class="table-th">Fallback Counsellor</th>
                            <th class="table-th">Collect Name</th>
                            <th class="table-th">Active</th>
                            <th class="table-th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($configs as $config)
                        <tr>
                            <td class="table-td font-medium">{{ $config->provider->value }}</td>
                            <td class="table-td text-gray-500">{{ $config->campus?->name ?? 'All campuses' }}</td>
                            <td class="table-td text-gray-500">{{ $config->fallbackCounsellor?->name ?? '—' }}</td>
                            <td class="table-td">{{ $config->collect_name ? 'Yes' : 'No' }}</td>
                            <td class="table-td">
                                <form method="POST" action="{{ route('crm.settings.ivr.toggle', $config->uuid) }}" class="inline">
                                    @csrf
                                    <button type="submit" @class(['badge cursor-pointer', 'badge-green' => $config->is_active, 'badge-red' => !$config->is_active])>
                                        {{ $config->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="table-td text-right space-x-2">
                                <a href="{{ route('crm.settings.ivr.edit', $config->uuid) }}" class="text-blue-600 hover:underline text-xs">Edit</a>
                                {{-- Hidden delete form --}}
                                <form id="form-del-ivr-{{ $config->uuid }}" method="POST" action="{{ route('crm.settings.ivr.destroy', $config->uuid) }}" class="hidden">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button"
                                        @click="$dispatch('confirm-delete', { formId: 'form-del-ivr-{{ $config->uuid }}', itemName: '{{ addslashes($config->provider->value) }} IVR' })"
                                        class="text-red-500 hover:text-red-700 text-xs font-medium cursor-pointer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="table-td text-center text-gray-400 py-8">No IVR configurations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $configs->links() }}</div>
            </div>
        </div>

    </div>

    <x-crm.confirm-modal variant="delete" subtext="This IVR configuration will be permanently removed and cannot be recovered." />
</x-layouts.crm>
