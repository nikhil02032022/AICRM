{{-- BRD: CRM-AG-004 — Commission structure list per agent --}}
<x-layouts.crm title="Commission Structures">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('crm.agents.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Commission Structures</h1>
                    <p class="text-sm text-gray-500">{{ $agent->name }}</p>
                </div>
            </div>
            <a href="{{ route('crm.agents.commission-structures.create', $agent) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Add Structure
            </a>
        </div>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Programme</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Rate</th>
                    <th class="px-4 py-3 text-left">Effective From</th>
                    <th class="px-4 py-3 text-left">Effective To</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($structures as $structure)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $structure->programme?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full bg-indigo-100 text-indigo-700 px-2 py-0.5 text-xs font-semibold">
                            {{ $structure->structure_type->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        @if($structure->structure_type->requiresPercentage())
                            {{ $structure->percentage }}%
                        @else
                            ₹{{ number_format($structure->amount, 2) }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $structure->effective_from->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $structure->effective_to?->format('d M Y') ?? 'Open-ended' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('crm.agents.commission-structures.edit', [$agent, $structure]) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No commission structures configured for this agent.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.crm>
