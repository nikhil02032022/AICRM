{{-- BRD: CRM-AR-008 — Drill-down to individual lead records from dashboard metric tiles --}}
<x-layouts.crm title="Drill-down: {{ $context['metric_label'] }}">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('crm.analytics.dashboards.institution') }}" class="hover:text-indigo-600">Analytics</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">{{ $context['metric_label'] }} — Drill-down</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $context['metric_label'] }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ \Carbon\Carbon::parse($context['from'])->format('d M Y') }}
            –
            {{ \Carbon\Carbon::parse($context['to'])->format('d M Y') }}
            @if($context['source'])
                &middot; Source: <span class="font-medium">{{ \App\Enums\CRM\LeadSource::tryFrom($context['source'])?->label() ?? $context['source'] }}</span>
            @endif
            @if($context['programme_name'])
                &middot; Programme: <span class="font-medium">{{ $context['programme_name'] }}</span>
            @endif
        </p>
    </x-slot:header>

    {{-- Results count + back link --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-600">
            {{ number_format($leads->total()) }} {{ Str::plural('record', $leads->total()) }} found
        </p>
        <a href="javascript:history.back()"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back
        </a>
    </div>

    {{-- Lead Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Lead</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Source</th>
                        <th class="table-th">Programme</th>
                        <th class="table-th">Counsellor</th>
                        <th class="table-th">Created</th>
                        <th class="table-th-center">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leads as $lead)
                    @php
                        $primaryProgramme = $lead->programmeInterests->first();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        {{-- Name + email --}}
                        <td class="table-td">
                            <div class="font-medium text-gray-900">
                                {{ $lead->first_name }} {{ $lead->last_name }}
                            </div>
                            @if($lead->email)
                                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $lead->email }}</div>
                            @endif
                        </td>

                        {{-- Status badge --}}
                        <td class="table-td">
                            @if($lead->status)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $lead->status->badgeClass() }}">
                                    {{ $lead->status->label() }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Source --}}
                        <td class="table-td">
                            @if($lead->source)
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600">
                                    {{ $lead->source->label() }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Primary Programme --}}
                        <td class="table-td text-gray-700">
                            {{ $primaryProgramme?->name ?? '—' }}
                        </td>

                        {{-- Counsellor --}}
                        <td class="table-td text-gray-600">
                            {{ $lead->assignedCounsellor?->name ?? '—' }}
                        </td>

                        {{-- Created at --}}
                        <td class="table-td text-gray-500 tabular-nums whitespace-nowrap">
                            {{ $lead->created_at->format('d M Y') }}
                        </td>

                        {{-- View link --}}
                        <td class="table-td-center">
                            @can('crm.leads.view')
                            <a href="{{ route('crm.leads.show', $lead->uuid) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-xs font-medium hover:underline">
                                View →
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="table-td text-center text-gray-400 py-12">
                            No leads match the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($leads->hasPages())
        <div class="border-t border-gray-100 px-5 py-3">
            {{ $leads->links() }}
        </div>
        @endif
    </div>
</x-layouts.crm>
