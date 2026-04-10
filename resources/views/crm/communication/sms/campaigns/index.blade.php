<x-layouts.crm title="SMS Campaigns">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">SMS Campaigns</h1>
                <p class="mt-1 text-sm text-gray-500">Bulk SMS to lead segments via registered DLT templates</p>
            </div>
            @can('crm.communication.send')
            <a href="{{ route('crm.communication.sms.campaigns.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Campaign
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
                            <th class="table-th">Name</th>
                            <th class="table-th">DLT Template</th>
                            <th class="table-th">Gateway</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Sent / Delivered / Failed</th>
                            <th class="table-th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($campaigns as $campaign)
                        <tr>
                            <td class="table-td font-medium">{{ $campaign->name }}</td>
                            <td class="table-td text-gray-500 truncate max-w-xs">{{ $campaign->dltTemplate?->template_name ?? '—' }}</td>
                            <td class="table-td">{{ $campaign->gateway }}</td>
                            <td class="table-td">
                                <span class="badge badge-blue">{{ $campaign->status->value }}</span>
                            </td>
                            <td class="table-td text-gray-500">{{ $campaign->sent_count }} / {{ $campaign->delivered_count }} / {{ $campaign->failed_count }}</td>
                            <td class="table-td text-right space-x-2">
                                @if ($campaign->status->value === 'DRAFT')
                                    {{-- Hidden launch form --}}
                                    <form id="form-launch-sms-{{ $campaign->uuid }}"
                                          method="POST"
                                          action="{{ route('crm.communication.sms.campaigns.launch', $campaign->uuid) }}"
                                          class="hidden">
                                        @csrf
                                    </form>
                                    <button type="button"
                                            @click="$dispatch('confirm-launch', { formId: 'form-launch-sms-{{ $campaign->uuid }}', itemName: '{{ addslashes($campaign->name) }}' })"
                                            class="text-green-600 hover:text-green-800 text-xs font-medium cursor-pointer">
                                        Launch
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="table-td text-center text-gray-400 py-8">No SMS campaigns yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $campaigns->links() }}</div>
            </div>
        </div>

    </div>

    <x-crm.confirm-modal
        variant="launch"
        title="Launch SMS campaign?"
        subtext="SMS messages will be dispatched immediately to all recipients in the segment."
        confirm-label="Yes, launch now"
    />
</x-layouts.crm>
