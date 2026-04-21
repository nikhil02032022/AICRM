{{-- BRD: CRM-FM-012 — Finance dashboard --}}
<x-layouts.crm title="Fee Dashboard">
    <div class="space-y-4">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Fee Dashboard</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Collected, pending, refunded and forecast revenue across programmes.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('crm.payments.fee-dashboard.index', array_merge($filters, ['export' => 'xlsx'])) }}"
                   class="btn-secondary-sm inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export XLSX
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="from" class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                    <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                </div>
                <div>
                    <label for="to" class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                    <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary-sm inline-flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Apply
                    </button>
                    <a href="{{ route('crm.payments.fee-dashboard.index') }}" class="btn-ghost-sm">Clear</a>
                </div>
            </form>
        </div>

        {{-- KPI tiles --}}
        @php
            $tiles = [
                ['key' => 'collected',         'label' => 'Collected',         'tone' => 'green'],
                ['key' => 'pending',           'label' => 'Pending',           'tone' => 'blue'],
                ['key' => 'refunded',          'label' => 'Refunded',          'tone' => 'amber'],
                ['key' => 'refunds_requested', 'label' => 'Refunds Requested', 'tone' => 'gray'],
            ];
            $tone = [
                'green' => ['ring' => 'ring-green-100', 'icon' => 'text-green-600', 'bg' => 'bg-green-50'],
                'blue'  => ['ring' => 'ring-blue-100',  'icon' => 'text-blue-600',  'bg' => 'bg-blue-50'],
                'amber' => ['ring' => 'ring-amber-100', 'icon' => 'text-amber-600', 'bg' => 'bg-amber-50'],
                'gray'  => ['ring' => 'ring-gray-100',  'icon' => 'text-gray-600',  'bg' => 'bg-gray-50'],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($tiles as $t)
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $t['label'] }}</p>
                        <div class="flex h-8 w-8 items-center justify-center rounded-md {{ $tone[$t['tone']]['bg'] }} ring-1 {{ $tone[$t['tone']]['ring'] }}">
                            <svg class="h-4 w-4 {{ $tone[$t['tone']]['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ number_format((float) ($data['summary'][$t['key']] ?? 0), 2) }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Forecast --}}
        <div class="rounded-lg border border-indigo-100 bg-gradient-to-r from-indigo-50 to-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-indigo-700">Revenue Forecast</p>
                    <p class="mt-1 text-xs text-gray-600">Sum of open (initiated + pending) transactions.</p>
                </div>
                <p class="text-3xl font-bold text-gray-900">
                    {{ number_format((float) ($data['forecast'] ?? 0), 2) }}
                </p>
            </div>
        </div>

        {{-- Programme breakdown --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
                <h3 class="text-sm font-semibold text-gray-700">Programme Breakdown</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Programme</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($data['programme_breakdown'] as $row)
                            @php
                                $rowClass = match($row['status']) {
                                    'success'   => 'bg-green-100 text-green-800',
                                    'pending',
                                    'initiated' => 'bg-blue-100 text-blue-800',
                                    'refunded'  => 'bg-amber-100 text-amber-800',
                                    'failed'    => 'bg-red-100 text-red-800',
                                    default     => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900">Programme #{{ $row['programme_id'] }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $rowClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $row['status'])) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ number_format((float) $row['total'], 2) }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-12 text-center">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-500">No data for this period</p>
                                    <p class="mt-1 text-xs text-gray-400">Adjust the date range and try again.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.crm>
