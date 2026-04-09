<x-layouts.crm>
    <x-slot:header>Edit: {{ $form->name }}</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ route('crm.forms.preview', $form->uuid) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 shadow-sm transition-colors hover:bg-amber-100 cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
            </svg>
            Preview
        </a>
        <a href="{{ route('crm.forms.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back
        </a>
    </x-slot:headerActions>

    <div class="max-w-4xl" x-data="webFormEditor(@json($form->fields ?? []))">

        {{-- Validation error summary --}}
        @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-800">Please fix the following errors:</p>
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('crm.forms.update', $form->uuid) }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">

                {{-- ── Basic Settings ── --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-5 text-base font-semibold text-gray-900">Form Settings</h2>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Form Name <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   value="{{ old('name', $form->name) }}"
                                   required maxlength="120"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
                            @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="slug" class="mb-1.5 block text-sm font-medium text-gray-700">URL Slug</label>
                            <div class="flex items-center rounded-lg border border-gray-300 bg-gray-50 overflow-hidden focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                <span class="flex-shrink-0 border-r border-gray-300 bg-gray-100 px-3 py-2.5 text-sm text-gray-500 font-mono">/f/</span>
                                <input type="text" id="slug" name="slug"
                                       value="{{ old('slug', $form->slug) }}"
                                       pattern="[a-z0-9\-]+" maxlength="80"
                                       class="block flex-1 bg-transparent px-3 py-2.5 text-sm text-gray-900 font-mono focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label for="source" class="mb-1.5 block text-sm font-medium text-gray-700">Lead Source</label>
                            <select id="source" name="source"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                                @foreach(\App\Enums\CRM\LeadSource::cases() as $src)
                                <option value="{{ $src->value }}" @selected(old('source', $form->source?->value) === $src->value)>{{ $src->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="redirect_url" class="mb-1.5 block text-sm font-medium text-gray-700">Thank-you Redirect URL</label>
                            <input type="url" id="redirect_url" name="redirect_url"
                                   value="{{ old('redirect_url', $form->redirect_url) }}"
                                   placeholder="https://yoursite.com/thank-you" maxlength="500"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="consent_form_version" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Consent Form Version <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="consent_form_version" name="consent_form_version"
                                   value="{{ old('consent_form_version', $form->consent_form_version) }}"
                                   maxlength="30" required
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="accent_color" class="mb-1.5 block text-sm font-medium text-gray-700">Accent Colour</label>
                            <input type="color" id="accent_color" name="accent_color"
                                   value="{{ old('accent_color', $form->accent_color ?? '#6366f1') }}"
                                   class="h-10 w-14 cursor-pointer rounded-md border border-gray-300">
                        </div>

                        <div class="flex items-center gap-3 pt-5">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   @checked(old('is_active', $form->is_active))
                                   class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Form is active</label>
                        </div>
                    </div>
                </div>

                {{-- ── Field Builder ── --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900">Form Fields</h2>
                        <button type="button" @click="addField"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 transition-colors hover:bg-indigo-100 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Add Field
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(field, index) in fields" :key="field._key">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                                    {{-- Hidden inputs --}}
                                    <input type="hidden" :name="'fields[' + index + '][id]'" :value="field.id">
                                    <input type="hidden" :name="'fields[' + index + '][type]'" :value="field.type">
                                    <input type="hidden" :name="'fields[' + index + '][label]'" :value="field.label">
                                    <input type="hidden" :name="'fields[' + index + '][required]'" :value="field.required ? 1 : 0">
                                    <input type="hidden" :name="'fields[' + index + '][placeholder]'" :value="field.placeholder">
                                    <input type="hidden" :name="'fields[' + index + '][options_raw]'" :value="field.optionsRaw">

                                    {{-- Label --}}
                                    <div>
                                        <label :for="'field-label-' + field._key" class="mb-1 block text-xs font-medium text-gray-600">Label <span class="text-red-500" aria-hidden="true">*</span></label>
                                        <input type="text" :id="'field-label-' + field._key"
                                               x-model="field.label"
                                               maxlength="160"
                                               placeholder="Field label"
                                               class="block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    </div>

                                    {{-- Type --}}
                                    <div>
                                        <label :for="'field-type-' + field._key" class="mb-1 block text-xs font-medium text-gray-600">Type</label>
                                        <select :id="'field-type-' + field._key"
                                                x-model="field.type"
                                                class="block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                                            <option value="text">Text</option>
                                            <option value="tel">Phone</option>
                                            <option value="email">Email</option>
                                            <option value="select">Dropdown</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="checkbox">Checkbox</option>
                                            <option value="hidden">Hidden</option>
                                        </select>
                                    </div>

                                    {{-- Required + Remove --}}
                                    <div class="flex items-end justify-between gap-3">
                                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" x-model="field.required"
                                                   class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            Required
                                        </label>
                                        <button type="button" @click="removeField(index)"
                                                class="cursor-pointer text-red-400 hover:text-red-600 transition-colors focus:outline-none"
                                                :aria-label="'Remove ' + field.label + ' field'">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Options (dropdown / checkbox only) --}}
                                    <div class="sm:col-span-3">
                                        <div x-show="field.type === 'select' || field.type === 'checkbox'" class="mb-3" x-transition>
                                            <label class="mb-1 block text-xs font-medium text-gray-600">
                                                Options
                                                <span class="ml-1 font-normal text-gray-400">(comma-separated)</span>
                                            </label>
                                            <input
                                                type="text"
                                                x-model="field.optionsRaw"
                                                placeholder="e.g. MBA, BBA, MCA, B.Tech"
                                                class="block w-full rounded-md border border-indigo-200 bg-white px-2.5 py-1.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                                :aria-label="'Options for ' + field.label">
                                            <p class="mt-1 text-xs text-gray-400">
                                                Each option separated by a comma.
                                                <template x-if="field.optionsRaw.trim()">
                                                    <span class="ml-2 text-indigo-600" x-text="'\u2192 ' + field.optionsRaw.split(',').map(s=>s.trim()).filter(Boolean).length + ' option(s)'" aria-live="polite"></span>
                                                </template>
                                            </p>
                                        </div>

                                        {{-- Conditional logic toggle --}}
                                        <button type="button" @click="field.showConditional = !field.showConditional"
                                                class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer focus:outline-none"
                                                :aria-expanded="field.showConditional">
                                            <span x-text="field.showConditional ? '\u25b2 Hide conditional logic' : '\u25b6 Add conditional logic (show_if)'"></span>
                                        </button>

                                        <div x-show="field.showConditional" class="mt-2 grid grid-cols-3 gap-2" x-transition>
                                            <input type="hidden" :name="'fields[' + index + '][show_if][field]'" :value="field.show_if?.field ?? ''">
                                            <input type="hidden" :name="'fields[' + index + '][show_if][operator]'" :value="field.show_if?.operator ?? 'equals'">
                                            <input type="hidden" :name="'fields[' + index + '][show_if][value]'" :value="field.show_if?.value ?? ''">

                                            <div>
                                                <label class="mb-1 block text-xs text-gray-500">When field ID</label>
                                                <input type="text" x-model="field.show_if.field"
                                                       placeholder="field_id"
                                                       class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs text-gray-500">Operator</label>
                                                <select x-model="field.show_if.operator"
                                                        class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-indigo-500 focus:outline-none cursor-pointer">
                                                    <option value="equals">equals</option>
                                                    <option value="not_equals">not equals</option>
                                                    <option value="contains">contains</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs text-gray-500">Value</label>
                                                <input type="text" x-model="field.show_if.value"
                                                       placeholder="MBA"
                                                       class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- No fields state --}}
                    <div x-show="fields.length === 0" class="mt-4 rounded-lg border-2 border-dashed border-gray-200 py-8 text-center">
                        <p class="text-sm text-gray-500">No custom fields. Core fields (Name, Mobile, Email) are always included.</p>
                        <button type="button" @click="addField"
                                class="mt-2 cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline focus:outline-none">
                            Add a custom field
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('crm.forms.index') }}"
                       class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
    function webFormEditor(initialFields) {
        return {
            fields: initialFields.map((f, i) => ({
                ...f,
                _key: i,
                optionsRaw: Array.isArray(f.options) ? f.options.join(', ') : (f.options_raw ?? ''),
                showConditional: !!f.show_if?.field,
                show_if: f.show_if || { field: '', operator: 'equals', value: '' },
            })),
            addField() {
                this.fields.push({
                    _key: Date.now(),
                    id: 'field_' + this.fields.length,
                    type: 'text',
                    label: '',
                    placeholder: '',
                    required: false,
                    optionsRaw: '',
                    showConditional: false,
                    show_if: { field: '', operator: 'equals', value: '' },
                });
            },
            removeField(index) {
                this.fields.splice(index, 1);
            },
        };
    }
    </script>
    @endpush
</x-layouts.crm>
