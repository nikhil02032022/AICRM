{{-- Partial: shared field form for custom field create/edit modals --}}
<div class="space-y-4">
    <div>
        <label for="field-label" class="block text-sm font-medium text-gray-700">Label <span class="text-red-500">*</span></label>
        <input
            id="field-label"
            type="text"
            x-model="form.label"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="e.g. Previous Institution"
            required
        >
    </div>

    <template x-if="'{{ $mode }}' === 'create'">
        <div>
            <label for="field-key" class="block text-sm font-medium text-gray-700">Field Key <span class="text-red-500">*</span></label>
            <input
                id="field-key"
                type="text"
                x-model="form.field_key"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                placeholder="auto_derived_from_label"
            >
            <p class="mt-1 text-xs text-gray-400">Auto-derived if left blank. Cannot be changed after creation.</p>
        </div>
    </template>

    <div>
        <label for="field-type" class="block text-sm font-medium text-gray-700">Field Type <span class="text-red-500">*</span></label>
        <select
            id="field-type"
            x-model="form.type"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        >
            <template x-for="ft in fieldTypes" :key="ft.value">
                <option :value="ft.value" x-text="ft.name.charAt(0) + ft.name.slice(1).toLowerCase()"></option>
            </template>
        </select>
    </div>

    {{-- Options builder for select type --}}
    <template x-if="form.type === 'select'">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Options</label>
            <div class="space-y-1.5">
                <template x-for="(opt, i) in form.options" :key="i">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 text-sm bg-gray-50 border border-gray-200 rounded px-3 py-1.5" x-text="opt.label"></span>
                        <button type="button" @click="removeOption(i)" class="text-red-400 hover:text-red-600 focus:outline-none" aria-label="Remove option">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>
            <div class="mt-2 flex gap-2">
                <input
                    type="text"
                    x-model="optionInput"
                    @keydown.enter.prevent="addOption()"
                    placeholder="Add option…"
                    class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                <button type="button" @click="addOption()" class="btn-secondary text-sm">Add</button>
            </div>
        </div>
    </template>

    <div class="flex items-center gap-3">
        <input
            id="field-required"
            type="checkbox"
            x-model="form.is_required"
            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        >
        <label for="field-required" class="text-sm text-gray-700">Required field</label>
    </div>
</div>
