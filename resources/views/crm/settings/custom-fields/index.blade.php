<x-layouts.crm title="Custom Fields">
    <div class="space-y-6"
         x-data="{
            activeEntity: '{{ request('entity', 'lead') }}',
            showAddModal: false,
            showEditModal: false,
            editField: null,
            saving: false,
            fieldTypes: @json(\App\Enums\CRM\CustomFieldType::cases()),
            form: { label: '', field_key: '', type: 'text', is_required: false, options: [] },
            optionInput: '',
            addOption() {
                if (this.optionInput.trim()) {
                    this.form.options.push({ label: this.optionInput.trim(), value: this.optionInput.trim().toLowerCase().replace(/\s+/g,'_') });
                    this.optionInput = '';
                }
            },
            removeOption(i) { this.form.options.splice(i, 1); },
            openEdit(field) {
                this.editField = field;
                this.form = { label: field.label, type: field.type, is_required: field.is_required, options: field.options || [] };
                this.showEditModal = true;
            },
            resetForm() { this.form = { label: '', field_key: '', type: 'text', is_required: false, options: [] }; this.optionInput = ''; },
            async saveField(mode) {
                this.saving = true;
                const url  = mode === 'create'
                    ? '{{ route('crm.settings.custom-fields.store') }}'
                    : '{{ url('crm/settings/custom-fields') }}/' + this.editField.uuid;
                const method = mode === 'create' ? 'POST' : 'PUT';
                const body = { ...this.form, entity: this.activeEntity };
                try {
                    const res = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                        body: JSON.stringify(body),
                    });
                    const json = await res.json();
                    if (json.success) { window.location.reload(); } else { alert(Object.values(json.errors || {}).flat().join('\n')); }
                } finally { this.saving = false; }
            },
            async deleteField(uuid) {
                if (!confirm('Delete this custom field? Existing values will be removed.')) return;
                await fetch('{{ url('crm/settings/custom-fields') }}/' + uuid, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                window.location.reload();
            },
         }"
    >
        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Custom Fields</h1>
                <p class="mt-1 text-sm text-gray-500">Define additional data fields captured on leads and applications</p>
            </div>
            @can('crm.settings.custom-fields.manage')
            <button type="button" @click="resetForm(); showAddModal = true" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Field
            </button>
            @endcan
        </div>

        {{-- Entity tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-4" aria-label="Entity tabs">
                @foreach(\App\Enums\CRM\CustomFieldEntity::cases() as $entity)
                <button
                    type="button"
                    @click="activeEntity = '{{ $entity->value }}'; window.location = '{{ route('crm.settings.custom-fields.index') }}?entity={{ $entity->value }}'"
                    :class="activeEntity === '{{ $entity->value }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-3 px-1 text-sm font-medium border-b-2 transition-colors duration-150 capitalize"
                    aria-current="{{ request('entity', 'lead') === $entity->value ? 'page' : 'false' }}"
                >
                    {{ ucfirst($entity->value) }}
                </button>
                @endforeach
            </nav>
        </div>

        {{-- Fields table --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Label</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Field Key</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Required</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($fields as $field)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $field->label }}</td>
                        <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $field->field_key }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ $field->type->value }}</td>
                        <td class="px-6 py-4">
                            @if($field->is_required)
                            <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">Required</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Optional</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($field->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">Active</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @can('crm.settings.custom-fields.manage')
                                <button
                                    type="button"
                                    @click="openEdit({{ $field->only(['uuid','label','type','is_required','options'])->toJson() }})"
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
                                    aria-label="Edit {{ $field->label }}"
                                >Edit</button>
                                <button
                                    type="button"
                                    @click="deleteField('{{ $field->uuid }}')"
                                    class="text-red-500 hover:text-red-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-red-500 rounded"
                                    aria-label="Delete {{ $field->label }}"
                                >Delete</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">
                            No custom fields defined for this entity yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Add Field Modal --}}
        <div
            x-show="showAddModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="add-field-title"
        >
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showAddModal = false"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 id="add-field-title" class="text-lg font-semibold text-gray-900">Add Custom Field</h2>
                    <button type="button" @click="showAddModal = false" aria-label="Close" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @include('crm.settings.custom-fields._field_form', ['mode' => 'create'])
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAddModal = false" class="btn-secondary">Cancel</button>
                    <button type="button" @click="saveField('create')" :disabled="saving" class="btn-primary disabled:opacity-60">
                        <span x-show="!saving">Add Field</span>
                        <span x-show="saving">Saving…</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Edit Field Modal --}}
        <div
            x-show="showEditModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="edit-field-title"
        >
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showEditModal = false"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 id="edit-field-title" class="text-lg font-semibold text-gray-900">Edit Custom Field</h2>
                    <button type="button" @click="showEditModal = false" aria-label="Close" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @include('crm.settings.custom-fields._field_form', ['mode' => 'edit'])
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showEditModal = false" class="btn-secondary">Cancel</button>
                    <button type="button" @click="saveField('edit')" :disabled="saving" class="btn-primary disabled:opacity-60">
                        <span x-show="!saving">Save Changes</span>
                        <span x-show="saving">Saving…</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-layouts.crm>
