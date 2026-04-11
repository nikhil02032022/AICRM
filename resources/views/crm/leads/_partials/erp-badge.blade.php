{{-- BRD: CRM-LC-020 — ERP Student Master match status badge --}}
{{-- Displayed in the lead profile sidebar; refreshed on page reload after job completes --}}
@if($lead->erp_match_status !== null)
<div class="border-t border-gray-100 pt-3 mt-1">
    <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">ERP Match</p>

    @php
        $erpStatus = $lead->erp_match_status;
        $badgeClass = match($erpStatus->value) {
            'matched'  => 'bg-green-50 border border-green-200 text-green-800',
            'no_match' => 'bg-gray-50 border border-gray-200 text-gray-600',
            'pending'  => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
            'error'    => 'bg-red-50 border border-red-200 text-red-700',
            default    => 'bg-gray-50 border border-gray-200 text-gray-600',
        };
        $iconPath = match($erpStatus->value) {
            'matched'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'no_match' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            'pending'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'error'    => 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
            default    => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    @endphp

    <div class="flex items-center gap-2 rounded-lg px-3 py-2 {{ $badgeClass }}">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
        </svg>
        <div class="min-w-0">
            <p class="text-xs font-semibold leading-tight">{{ $erpStatus->label() }}</p>
            @if($erpStatus->value === 'matched' && $lead->erp_student_uuid)
                <p class="mt-0.5 truncate font-mono text-[10px] opacity-70">{{ $lead->erp_student_uuid }}</p>
            @elseif($erpStatus->value === 'matched')
                <p class="mt-0.5 text-[10px] opacity-70">ERP record linked</p>
            @endif
        </div>
    </div>

    @can('crm.leads.edit', $lead)
    @if($erpStatus->value !== 'pending')
    <form method="POST" action="{{ route('crm.leads.check-erp', $lead->uuid) }}" class="mt-2">
        @csrf
        <button type="submit"
                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors">
            Re-check ERP
        </button>
    </form>
    @endif
    @endcan
</div>
@else
{{-- Null = never checked; show a subtle prompt for admins --}}
@can('crm.leads.edit', $lead)
<div class="border-t border-gray-100 pt-3 mt-1">
    <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">ERP Match</p>
    <form method="POST" action="{{ route('crm.leads.check-erp', $lead->uuid) }}">
        @csrf
        <button type="submit"
                class="w-full rounded-lg border border-dashed border-gray-300 bg-transparent px-3 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-50 hover:border-gray-400 transition-colors">
            Check ERP Student Master
        </button>
    </form>
</div>
@endcan
@endif
