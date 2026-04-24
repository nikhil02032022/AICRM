<x-layouts.crm :title="'Walk-in Queue — ' . $campus->name">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Walk-in Queue</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $campus->name }} — {{ now()->format('d M Y') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('crm.walk-in-queue.stats') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Daily Stats
            </a>
            <a href="{{ route('public.queue.display', $campus->institution) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Display Screen
            </a>
        </div>
    </div>

    <livewire:crm.counselling.walk-in-queue :campus="$campus" />

</x-layouts.crm>
