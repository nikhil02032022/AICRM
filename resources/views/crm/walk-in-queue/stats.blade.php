<x-layouts.crm :title="'Queue Stats — ' . $campus->name">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Walk-in Queue — Daily Stats</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $campus->name }} · {{ now()->format('d M Y') }}</p>
        </div>
        <a href="{{ route('crm.walk-in-queue.index') }}"
           class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
            Back to Queue
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @php
            $tiles = [
                ['label' => 'Total Tokens', 'value' => $stats['total'], 'colour' => 'gray'],
                ['label' => 'Served', 'value' => $stats['served'], 'colour' => 'green'],
                ['label' => 'Skipped', 'value' => $stats['skipped'], 'colour' => 'slate'],
                ['label' => 'Waiting', 'value' => $stats['waiting'], 'colour' => 'blue'],
            ];
        @endphp
        @foreach ($tiles as $tile)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm text-center">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $tile['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 tabular-nums">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($stats['avg_wait_minutes'] !== null)
        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm text-center inline-block">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Avg. Wait Time</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 tabular-nums">{{ $stats['avg_wait_minutes'] }} min</p>
        </div>
    @endif

</x-layouts.crm>
