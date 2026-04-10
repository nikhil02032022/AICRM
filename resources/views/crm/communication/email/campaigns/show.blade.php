<x-layouts.crm :title="'Campaign: ' . $campaign->name">
    <div
        class="space-y-6"
        x-data="{}"
        @keydown.escape.window=""
    >

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $campaign->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $campaign->subject }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('crm.communication.email.campaigns.index') }}" class="btn-secondary inline-flex">← Back to campaigns</a>
                <span @class([
                    'badge',
                    'badge-yellow' => $campaign->status?->value === 'DRAFT',
                    'badge-blue'   => $campaign->status?->value === 'SCHEDULED',
                    'badge-indigo' => $campaign->status?->value === 'SENDING',
                    'badge-green'  => $campaign->status?->value === 'SENT',
                    'badge-gray'   => $campaign->status?->value === 'PAUSED',
                    'badge-red'    => $campaign->status?->value === 'CANCELLED',
                ])>{{ $campaign->status?->label() ?? '—' }}</span>
                @if ($campaign->status?->value === 'DRAFT')
                    <form id="show-launch-form" method="POST" action="{{ route('crm.communication.email.campaigns.launch', $campaign->uuid) }}" class="hidden">
                        @csrf
                    </form>
                    <button
                        type="button"
                        @click="$dispatch('confirm-launch', { formId: 'show-launch-form', itemName: '{{ addslashes($campaign->name) }}' })"
                        class="btn-primary"
                    >
                        Launch Now
                    </button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        {{-- Campaign Details --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <colgroup>
                        <col style="width:180px" />
                        <col class="w-auto" />
                    </colgroup>
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Field
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Value
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Template</td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $campaign->template?->name ?? '—' }}</td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">From</td>
                            <td class="px-6 py-4 text-gray-700">
                                {{ $campaign->from_name }}
                                <span class="text-gray-400">&lt;{{ $campaign->from_email }}&gt;</span>
                            </td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Scheduled</td>
                            <td class="px-6 py-4 text-gray-700">{{ $campaign->scheduled_at?->format('d M Y, H:i') ?? 'Not scheduled' }}</td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Sent At</td>
                            <td class="px-6 py-4 text-gray-700">{{ $campaign->sent_at?->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Delivery Stats --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <colgroup>
                        <col class="w-auto" />
                        <col style="width:120px" />
                        <col style="width:120px" />
                    </colgroup>
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Metric
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Count
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Rate
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @php
                            $base = max($campaign->total_recipients, 1);
                            $sent = $campaign->sent_count;
                            $sentBase = max($sent, 1);
                        @endphp
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Total Recipients</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ number_format($campaign->total_recipients) }}</td>
                            <td class="px-6 py-4 text-right text-gray-400">—</td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Sent</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ number_format($sent) }}</td>
                            <td class="px-6 py-4 text-right text-gray-500">{{ number_format($sent / $base * 100, 1) }}%</td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Delivered</td>
                            <td class="px-6 py-4 text-right font-semibold text-blue-600">{{ number_format($campaign->delivered_count) }}</td>
                            <td class="px-6 py-4 text-right text-gray-500">{{ number_format($campaign->delivered_count / $sentBase * 100, 1) }}%</td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Opened</td>
                            <td class="px-6 py-4 text-right font-semibold text-indigo-600">{{ number_format($campaign->opened_count) }}</td>
                            <td class="px-6 py-4 text-right text-gray-500">{{ number_format($campaign->opened_count / $sentBase * 100, 1) }}%</td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Clicked</td>
                            <td class="px-6 py-4 text-right font-semibold text-green-600">{{ number_format($campaign->clicked_count) }}</td>
                            <td class="px-6 py-4 text-right text-gray-500">{{ number_format($campaign->clicked_count / $sentBase * 100, 1) }}%</td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Bounced</td>
                            <td class="px-6 py-4 text-right font-semibold text-red-500">{{ number_format($campaign->bounced_count) }}</td>
                            <td class="px-6 py-4 text-right text-gray-500">{{ number_format($campaign->bounced_count / $sentBase * 100, 1) }}%</td>
                        </tr>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Unsubscribed</td>
                            <td class="px-6 py-4 text-right font-semibold text-amber-600">{{ number_format($campaign->unsubscribed_count) }}</td>
                            <td class="px-6 py-4 text-right text-gray-500">{{ number_format($campaign->unsubscribed_count / $sentBase * 100, 1) }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Launch Confirmation Modal ── --}}
        <x-crm.confirm-modal variant="launch" />

    </div>
</x-layouts.crm>
