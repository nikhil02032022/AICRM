<x-layouts.crm title="Email Campaigns">
    <div
        class="space-y-6"
        x-data="{}"
        @keydown.escape.window=""
    >

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Email Campaigns</h1>
                <p class="mt-1 text-sm text-gray-500">Bulk email campaigns to lead segments</p>
            </div>
            @can('crm.communication.send')
            <a href="{{ route('crm.communication.email.campaigns.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Campaign
            </a>
            @endcan
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Campaign</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-32">Status</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-44">Sent / Opens / Clicks</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-40">Scheduled</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 w-40">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($campaigns as $campaign)
                    @php
                        $statusColors = match($campaign->status?->value) {
                            'DRAFT'     => ['dot' => 'bg-amber-400',   'text' => 'text-amber-700',   'bg' => 'bg-amber-50'],
                            'SCHEDULED' => ['dot' => 'bg-blue-400',    'text' => 'text-blue-700',    'bg' => 'bg-blue-50'],
                            'SENDING'   => ['dot' => 'bg-indigo-400',  'text' => 'text-indigo-700',  'bg' => 'bg-indigo-50'],
                            'SENT'      => ['dot' => 'bg-emerald-400', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50'],
                            'PAUSED'    => ['dot' => 'bg-gray-400',    'text' => 'text-gray-600',    'bg' => 'bg-gray-100'],
                            'CANCELLED' => ['dot' => 'bg-red-400',     'text' => 'text-red-600',     'bg' => 'bg-red-50'],
                            default     => ['dot' => 'bg-gray-300',    'text' => 'text-gray-500',    'bg' => 'bg-gray-50'],
                        };
                    @endphp
                    <tr class="group hover:bg-gray-50/70 transition-colors duration-100">

                        {{-- Campaign name + subject sub-line --}}
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-gray-900 leading-snug">{{ $campaign->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-400 truncate max-w-sm">
                                {{ $campaign->subject }}
                                @if($campaign->from_email)
                                    &nbsp;·&nbsp;<span class="text-gray-400">{{ $campaign->from_name }} &lt;{{ $campaign->from_email }}&gt;</span>
                                @endif
                            </p>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusColors['bg'] }} {{ $statusColors['text'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusColors['dot'] }}" aria-hidden="true"></span>
                                {{ $campaign->status?->label() ?? '—' }}
                            </span>
                        </td>

                        {{-- Sent / Opens / Clicks --}}
                        <td class="px-4 py-4 text-center">
                            <span class="text-xs text-gray-700 font-medium tabular-nums">
                                {{ number_format($campaign->sent_count) }}
                                <span class="text-gray-300 mx-0.5">/</span>
                                {{ number_format($campaign->opened_count) }}
                                <span class="text-gray-300 mx-0.5">/</span>
                                {{ number_format($campaign->clicked_count) }}
                            </span>
                        </td>

                        {{-- Scheduled --}}
                        <td class="px-4 py-4 text-center">
                            <span class="text-xs text-gray-400">{{ $campaign->scheduled_at?->format('d M Y, H:i') ?? '—' }}</span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-0 divide-x divide-gray-200">

                                <a href="{{ route('crm.communication.email.campaigns.show', $campaign->uuid) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded"
                                   aria-label="View {{ $campaign->name }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    </svg>
                                    View
                                </a>

                                @if ($campaign->status?->value === 'DRAFT')
                                {{-- Hidden form submitted by the modal --}}
                                <form id="launch-form-{{ $campaign->uuid }}"
                                      method="POST"
                                      action="{{ route('crm.communication.email.campaigns.launch', $campaign->uuid) }}"
                                      class="hidden">
                                    @csrf
                                </form>
                                <button type="button"
                                        @click="$dispatch('confirm-launch', { formId: 'launch-form-{{ $campaign->uuid }}', itemName: '{{ addslashes($campaign->name) }}' })"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-emerald-600 hover:text-emerald-800 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-400 rounded cursor-pointer"
                                        aria-label="Launch {{ $campaign->name }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                                    </svg>
                                    Launch
                                </button>
                                @endif

                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-gray-500">No campaigns yet</p>
                            <p class="mt-1 text-xs text-gray-400">Create your first bulk email campaign to get started.</p>
                            @can('crm.communication.send')
                            <a href="{{ route('crm.communication.email.campaigns.create') }}" class="btn-primary-sm mt-5">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                New Campaign
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($campaigns->hasPages())
            <div class="border-t border-gray-100 px-6 py-3">
                {{ $campaigns->links() }}
            </div>
            @endif
        </div>

        {{-- ── Launch Confirmation Modal ── --}}
        <x-crm.confirm-modal variant="launch" />

    </div>
</x-layouts.crm>
