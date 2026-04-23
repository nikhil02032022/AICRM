{{-- BRD: CRM-AG-004 — Edit commission structure --}}
<x-layouts.crm title="Edit Commission Structure">
    <x-slot:header>
        <div class="flex items-center gap-3">
            <a href="{{ route('crm.agents.commission-structures.index', $agent) }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h1 class="text-xl font-semibold text-gray-900">Edit Commission Structure — {{ $agent->name }}</h1>
        </div>
    </x-slot:header>

    <div class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('crm.agents.commission-structures.update', [$agent, $commissionStructure]) }}"
              class="space-y-5" x-data="{ structureType: '{{ old('structure_type', $commissionStructure->structure_type->value) }}' }">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Programme</label>
                    <select name="programme_id" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach($programmes as $id => $name)
                            <option value="{{ $id }}" @selected(old('programme_id', $commissionStructure->programme_id) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Commission Type</label>
                    <select name="structure_type" x-model="structureType"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach($structureTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('structure_type', $commissionStructure->structure_type->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="structureType !== 'percentage_fee'">
                    <label class="block text-sm font-medium text-gray-700">Fixed Amount (₹)</label>
                    <input type="number" name="amount" step="0.01" min="0"
                           value="{{ old('amount', $commissionStructure->amount) }}"
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div x-show="structureType === 'percentage_fee'">
                    <label class="block text-sm font-medium text-gray-700">Percentage (%)</label>
                    <input type="number" name="percentage" step="0.01" min="0" max="100"
                           value="{{ old('percentage', $commissionStructure->percentage) }}"
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Effective From</label>
                        <input type="date" name="effective_from" required
                               value="{{ old('effective_from', $commissionStructure->effective_from->toDateString()) }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Effective To</label>
                        <input type="date" name="effective_to"
                               value="{{ old('effective_to', $commissionStructure->effective_to?->toDateString()) }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('crm.agents.commission-structures.index', $agent) }}"
                   class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Changes</button>
            </div>
        </form>
    </div>
</x-layouts.crm>
