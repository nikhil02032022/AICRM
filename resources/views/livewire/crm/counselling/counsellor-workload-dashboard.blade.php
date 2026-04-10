<div>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900">Counsellor Workload</h3>
        <button wire:click="$refresh"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm hover:bg-gray-50">
            Refresh
        </button>
    </div>

    @if($this->counsellors->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
            <p class="text-sm text-gray-500">No counsellors found under the current load cap.</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach($this->counsellors as $counsellor)
                @php
                    $count = (int) $counsellor->active_lead_count;
                    $max   = auth()->user()->institution?->counsellor_assignment_config?->max_leads_per_counsellor ?? 50;
                    $pct   = $max > 0 ? min(100, (int) round(($count / $max) * 100)) : 0;
                    $barColour = $pct >= 80 ? 'bg-red-400' : ($pct >= 50 ? 'bg-amber-400' : 'bg-emerald-400');
                @endphp
                <div class="rounded-lg border border-gray-100 bg-white p-3 shadow-sm">
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-800">{{ $counsellor->name }}</span>
                        <span class="text-xs font-mono font-semibold text-gray-500">{{ $count }} active</span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-100">
                        <div class="h-1.5 rounded-full {{ $barColour }} transition-all duration-300"
                             style="width: {{ $pct }}%"
                             role="progressbar"
                             aria-valuenow="{{ $count }}"
                             aria-valuemax="{{ $max }}"
                             aria-label="{{ $counsellor->name }} active leads"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
