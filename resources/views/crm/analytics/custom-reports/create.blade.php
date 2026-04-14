<x-layouts.crm title="New Custom Report">

@php
    $oldSelectedFields = old('selected_fields', []);
    $oldFilters = collect(old('filters', []))
        ->map(fn ($f) => [
            'field'    => $f['field']    ?? '',
            'operator' => $f['operator'] ?? '=',
            'value'    => $f['value']    ?? '',
        ])
        ->values()
        ->all();
@endphp

<div
    class="max-w-2xl space-y-6"
    x-data="{
        entity: '{{ old('entity', 'leads') }}',
        selectedFields: @json($oldSelectedFields),
        filters: @json($oldFilters),
        sortDirection: '{{ old('sort_direction', 'asc') }}',
        availableFields: @json($availableFields ?? []),
        operators: ['=','!=','>','<','>=','<=','like','not like','is null','is not null'],
        addFilter() { this.filters.push({ field: '', operator: '=', value: '' }); },
        removeFilter(i) { this.filters.splice(i, 1); },
        toggleField(f) {
            const idx = this.selectedFields.indexOf(f);
            if (idx > -1) this.selectedFields.splice(idx, 1);
            else this.selectedFields.push(f);
        },
        isSelected(f) { return this.selectedFields.includes(f); },
    }"
>

    {{-- Page header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('crm.reports.custom.index') }}"
           aria-label="Back to Custom Reports"
           class="flex items-center justify-center h-9 w-9 rounded-lg border border-gray-200 bg-white text-gray-400 shadow-sm hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">New Custom Report</h1>
            <p class="text-sm text-gray-500">Define what data to pull, filter, and sort.</p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('crm.reports.custom.store') }}" class="space-y-5">
        @csrf

        {{-- ── 1. Basic details ── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Report Details</h2>
            </div>
            <div class="p-5 space-y-4">

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Report Name <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autocomplete="off"
                        placeholder="e.g. Monthly Lead Source Analysis"
                        @class([
                            'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
                            'border-red-400 focus:border-red-500 focus:ring-red-500' => $errors->has('name'),
                        ])
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description <span class="text-xs text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="2"
                        placeholder="What does this report show?"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"
                    >{{ old('description') }}</textarea>
                </div>

                {{-- Entity --}}
                <div>
                    <label for="entity" class="block text-sm font-medium text-gray-700 mb-1">
                        Data Entity <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select
                        id="entity"
                        name="entity"
                        x-model="entity"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach(\App\Enums\CRM\ReportEntity::cases() as $e)
                            <option value="{{ $e->value }}" {{ old('entity', 'leads') === $e->value ? 'selected' : '' }}>
                                {{ ucfirst($e->value) }}
                            </option>
                        @endforeach
                    </select>
                    @error('entity')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- ── 2. Columns ── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Columns to Include</h2>
                <span
                    class="text-xs font-medium"
                    :class="selectedFields.length > 0 ? 'text-indigo-600' : 'text-gray-400'"
                    x-text="selectedFields.length + ' selected'"
                ></span>
            </div>
            <div class="p-5">
                @if(count($availableFields ?? []) > 0)
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach($availableFields ?? [] as $key => $label)
                        <label
                            class="flex items-center gap-2 rounded-lg border px-3 py-2.5 cursor-pointer transition-colors duration-150"
                            :class="isSelected('{{ $key }}')
                                ? 'border-indigo-400 bg-indigo-50'
                                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                        >
                            <input
                                type="checkbox"
                                name="selected_fields[]"
                                value="{{ $key }}"
                                @change="toggleField('{{ $key }}')"
                                {{ in_array($key, $oldSelectedFields) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 shrink-0"
                            >
                            <span
                                class="text-sm leading-tight"
                                :class="isSelected('{{ $key }}') ? 'text-indigo-800 font-medium' : 'text-gray-700'"
                            >{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="selectedFields = Object.keys(availableFields)"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium cursor-pointer focus:outline-none focus:underline">
                            Select all
                        </button>
                        <span class="text-gray-300" aria-hidden="true">·</span>
                        <button type="button" @click="selectedFields = []"
                            class="text-xs text-gray-500 hover:text-gray-700 font-medium cursor-pointer focus:outline-none focus:underline">
                            Clear all
                        </button>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No fields available for the selected entity.</p>
                @endif
                @error('selected_fields')
                    <p class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- ── 3. Filters ── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Filters</h2>
                <button
                    type="button"
                    @click="addFilter()"
                    class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 cursor-pointer focus:outline-none focus:underline"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add filter
                </button>
            </div>
            <div class="p-5 space-y-2">
                <template x-for="(filter, i) in filters" :key="i">
                    <div
                        class="flex items-center gap-2"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                    >
                        <select :name="`filters[${i}][field]`" x-model="filter.field"
                            :aria-label="'Filter ' + (i+1) + ' field'"
                            class="flex-1 min-w-0 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Field —</option>
                            @foreach($availableFields ?? [] as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select :name="`filters[${i}][operator]`" x-model="filter.operator"
                            :aria-label="'Filter ' + (i+1) + ' operator'"
                            class="w-32 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                            <template x-for="op in operators" :key="op">
                                <option :value="op" x-text="op"></option>
                            </template>
                        </select>
                        <input
                            type="text"
                            :name="`filters[${i}][value]`"
                            x-model="filter.value"
                            placeholder="Value"
                            :aria-label="'Filter ' + (i+1) + ' value'"
                            x-show="filter.operator !== 'is null' && filter.operator !== 'is not null'"
                            class="w-36 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                        <button
                            type="button"
                            @click="removeFilter(i)"
                            :aria-label="'Remove filter ' + (i+1)"
                            class="flex items-center justify-center h-8 w-8 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors duration-150 cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-400 shrink-0"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <p x-show="filters.length === 0" class="text-sm text-gray-400">
                    No filters — report will include all records.
                </p>
            </div>
        </div>

        {{-- ── 4. Sort ── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Sort Order</h2>
            </div>
            <div class="p-5 grid grid-cols-2 gap-4">
                <div>
                    <label for="sort_field" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select id="sort_field" name="sort_field"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">— None —</option>
                        @foreach($availableFields ?? [] as $key => $label)
                            <option value="{{ $key }}" {{ old('sort_field') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sort_direction" class="block text-sm font-medium text-gray-700 mb-1">Direction</label>
                    <select id="sort_direction" name="sort_direction"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="asc" {{ old('sort_direction', 'asc') === 'asc' ? 'selected' : '' }}>Ascending</option>
                        <option value="desc" {{ old('sort_direction') === 'desc' ? 'selected' : '' }}>Descending</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ── Actions ── --}}
        <div class="flex items-center justify-end gap-3 pt-1">
            <a href="{{ route('crm.reports.custom.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">
                <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Report
            </button>
        </div>

    </form>

</div>
</x-layouts.crm>
