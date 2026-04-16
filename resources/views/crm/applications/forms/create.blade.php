<x-layouts.crm>
    <x-slot:header>{{ isset($template) ? 'Edit Application Form Template' : 'Create Application Form Template' }}</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ route('crm.applications.forms.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back
        </a>
    </x-slot:headerActions>

    @php
        $isEdit = isset($template);
        $formAction = $isEdit ? route('crm.applications.forms.update', $template->uuid) : route('crm.applications.forms.store');
        $initialSections = old('sections', $template->sections ?? [
            [
                'id' => 'personal_details',
                'title' => 'Personal Details',
                'order' => 1,
                'description' => 'Capture applicant identity and contact information.',
                'fields' => [
                    ['id' => 'first_name', 'type' => 'text', 'label' => 'First Name', 'required' => true, 'placeholder' => null],
                    ['id' => 'last_name', 'type' => 'text', 'label' => 'Last Name', 'required' => true, 'placeholder' => null],
                    ['id' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'placeholder' => null],
                    ['id' => 'mobile', 'type' => 'tel', 'label' => 'Mobile Number', 'required' => true, 'placeholder' => null],
                    ['id' => 'date_of_birth', 'type' => 'date', 'label' => 'Date of Birth', 'required' => true, 'placeholder' => null],
                ],
            ],
            [
                'id' => 'academic_history',
                'title' => 'Academic History',
                'order' => 2,
                'description' => 'Capture applicant qualifications and marks.',
                'fields' => [
                    ['id' => 'highest_qualification', 'type' => 'text', 'label' => 'Highest Qualification', 'required' => true, 'placeholder' => null],
                    ['id' => 'board_or_university', 'type' => 'text', 'label' => 'Board / University', 'required' => true, 'placeholder' => null],
                    ['id' => 'passing_year', 'type' => 'number', 'label' => 'Passing Year', 'required' => true, 'placeholder' => null],
                    ['id' => 'aggregate_percentage', 'type' => 'number', 'label' => 'Aggregate Percentage', 'required' => true, 'placeholder' => null],
                ],
            ],
            [
                'id' => 'entrance_exam_scores',
                'title' => 'Entrance Exam Scores',
                'order' => 3,
                'description' => 'Capture entrance exam details and rank.',
                'fields' => [
                    ['id' => 'exam_name', 'type' => 'text', 'label' => 'Exam Name', 'required' => false, 'placeholder' => null],
                    ['id' => 'exam_roll_number', 'type' => 'text', 'label' => 'Roll Number', 'required' => false, 'placeholder' => null],
                    ['id' => 'exam_score', 'type' => 'number', 'label' => 'Score', 'required' => false, 'placeholder' => null],
                    ['id' => 'exam_rank', 'type' => 'number', 'label' => 'Rank', 'required' => false, 'placeholder' => null],
                ],
            ],
            [
                'id' => 'co_curricular_activities',
                'title' => 'Co-curricular Activities',
                'order' => 4,
                'description' => 'Capture achievements beyond academics.',
                'fields' => [
                    ['id' => 'activities_summary', 'type' => 'textarea', 'label' => 'Activities Summary', 'required' => false, 'placeholder' => null],
                    ['id' => 'achievements', 'type' => 'textarea', 'label' => 'Achievements', 'required' => false, 'placeholder' => null],
                ],
            ],
            [
                'id' => 'declarations',
                'title' => 'Declarations',
                'order' => 5,
                'description' => 'Capture statutory and policy declarations.',
                'fields' => [
                    ['id' => 'terms_accepted', 'type' => 'checkbox', 'label' => 'I confirm all submitted information is accurate.', 'required' => true, 'placeholder' => null],
                    ['id' => 'privacy_consent', 'type' => 'checkbox', 'label' => 'I consent to processing of my data as per policy.', 'required' => true, 'placeholder' => null],
                ],
            ],
            [
                'id' => 'digital_signature',
                'title' => 'Digital Signature',
                'order' => 6,
                'description' => 'Capture applicant digital signature before submission.',
                'fields' => [
                    ['id' => 'applicant_signature', 'type' => 'signature', 'label' => 'Applicant Signature', 'required' => true, 'placeholder' => null],
                ],
            ],
        ]);
        $initialRules = old('progression_rules', $template->progression_rules ?? []);
        $initialSettings = old('settings', $template->settings ?? []);
    @endphp

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
            <p class="text-sm font-semibold text-red-800">Please resolve the highlighted validation errors.</p>
            <ul class="mt-1 list-inside list-disc text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" x-data="applicationBuilder(@js($initialSections), @js($initialRules))" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-gray-900">Template Settings</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Template Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" required maxlength="150"
                           value="{{ old('name', $template->name ?? '') }}" @input="syncSlug"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="slug" class="mb-1 block text-sm font-medium text-gray-700">Slug</label>
                    <input id="slug" name="slug" x-model="slug" maxlength="120" placeholder="ug-application-2026"
                           value="{{ old('slug', $template->slug ?? '') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 font-mono focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="minimum_completeness_percentage" class="mb-1 block text-sm font-medium text-gray-700">Minimum Completeness %</label>
                    <input id="minimum_completeness_percentage" name="minimum_completeness_percentage" type="number" min="1" max="100"
                           value="{{ old('minimum_completeness_percentage', $template->minimum_completeness_percentage ?? 100) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="2" maxlength="1000"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('description', $template->description ?? '') }}</textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $template->is_active ?? true))
                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active template</label>
                </div>

                <div class="sm:col-span-2">
                    <h3 class="mb-2 text-sm font-semibold text-gray-900">AP-003 and AP-004 Settings</h3>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                            <input type="hidden" name="settings[allow_save_and_resume]" value="0">
                            <input type="checkbox" name="settings[allow_save_and_resume]" value="1"
                                   @checked((bool) ($initialSettings['allow_save_and_resume'] ?? false))
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Allow save and resume
                        </label>

                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                            <input type="hidden" name="settings[mobile_optimised]" value="0">
                            <input type="checkbox" name="settings[mobile_optimised]" value="1"
                                   @checked((bool) ($initialSettings['mobile_optimised'] ?? true))
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Mobile optimised
                        </label>

                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                            <input type="hidden" name="settings[show_progress_bar]" value="0">
                            <input type="checkbox" name="settings[show_progress_bar]" value="1"
                                   @checked((bool) ($initialSettings['show_progress_bar'] ?? true))
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Show progress bar
                        </label>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                            <input type="hidden" name="settings[application_fee_enabled]" value="0">
                            <input type="checkbox" name="settings[application_fee_enabled]" value="1"
                                   @checked((bool) ($initialSettings['application_fee_enabled'] ?? false))
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Enable application fee
                        </label>

                        <div>
                            <label for="application_fee_amount" class="mb-1 block text-xs font-medium text-gray-600">Application Fee Amount</label>
                            <input id="application_fee_amount" name="settings[application_fee_amount]" type="number" min="0" step="0.01"
                                   value="{{ old('settings.application_fee_amount', $initialSettings['application_fee_amount'] ?? '') }}"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="application_fee_currency" class="mb-1 block text-xs font-medium text-gray-600">Currency (ISO)</label>
                            <input id="application_fee_currency" name="settings[application_fee_currency]" type="text" maxlength="3"
                                   value="{{ old('settings.application_fee_currency', $initialSettings['application_fee_currency'] ?? 'INR') }}"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                            <input type="hidden" name="settings[allow_multi_programme_applications]" value="0">
                            <input type="checkbox" name="settings[allow_multi_programme_applications]" value="1"
                                   @checked((bool) ($initialSettings['allow_multi_programme_applications'] ?? false))
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Allow multi-programme applications
                        </label>

                        <div>
                            <label for="max_programmes_per_application" class="mb-1 block text-xs font-medium text-gray-600">Max Programmes per Application</label>
                            <input id="max_programmes_per_application" name="settings[max_programmes_per_application]" type="number" min="1" max="10"
                                   value="{{ old('settings.max_programmes_per_application', $initialSettings['max_programmes_per_application'] ?? 1) }}"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Multi-step Sections</h2>
                <button type="button" @click="addSection"
                        class="rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                    Add Section
                </button>
            </div>

            <div class="mb-4 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-indigo-900">AP-002 Readiness Preview</h3>
                        <p class="mt-1 text-xs text-indigo-700">Verifies required section coverage and digital signature readiness before save.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                          :class="ap002Ready() ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                          x-text="ap002Ready() ? 'Ready for AP-002' : 'Action Needed'"></span>
                </div>

                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="requiredSectionId in requiredSectionIds" :key="requiredSectionId">
                        <div class="rounded-lg border px-3 py-2 text-xs"
                             :class="sectionExists(requiredSectionId) ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'">
                            <p class="font-medium" x-text="requiredSectionId"></p>
                            <p x-text="sectionExists(requiredSectionId) ? 'Present' : 'Missing'"></p>
                        </div>
                    </template>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 font-medium"
                          :class="hasDigitalSignatureField() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                          x-text="hasDigitalSignatureField() ? 'Signature field found' : 'Signature field missing'"></span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 font-medium"
                          :class="hasDuplicateSectionIds() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                          x-text="hasDuplicateSectionIds() ? 'Duplicate section IDs detected' : 'Section IDs unique'"></span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 font-medium"
                          :class="hasDuplicateFieldIds() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                          x-text="hasDuplicateFieldIds() ? 'Duplicate field IDs detected' : 'Field IDs unique per section'"></span>
                </div>
            </div>

            <div class="space-y-4">
                <template x-for="(section, sIndex) in sections" :key="section._key">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Section ID</label>
                                <input type="text" x-model="section.id" :name="`sections[${sIndex}][id]`"
                                       class="block w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Title</label>
                                <input type="text" x-model="section.title" :name="`sections[${sIndex}][title]`"
                                       class="block w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-sm">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="w-full">
                                    <label class="mb-1 block text-xs font-medium text-gray-600">Order</label>
                                    <input type="number" min="1" x-model="section.order" :name="`sections[${sIndex}][order]`"
                                           class="block w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-sm">
                                </div>
                                <button type="button" @click="removeSection(sIndex)" class="rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">Remove</button>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Section Description</label>
                            <input type="text" x-model="section.description" :name="`sections[${sIndex}][description]`"
                                   class="block w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-sm">
                        </div>

                        <div class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-xs font-semibold text-gray-700">Fields</p>
                                <button type="button" @click="addField(sIndex)" class="text-xs font-medium text-indigo-700 hover:text-indigo-900">+ Add Field</button>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(field, fIndex) in section.fields" :key="field._key">
                                    <div class="grid grid-cols-1 gap-2 rounded-md border border-gray-200 bg-white p-2 md:grid-cols-5">
                                        <input type="text" x-model="field.id" :name="`sections[${sIndex}][fields][${fIndex}][id]`" placeholder="field_id"
                                               class="rounded border border-gray-300 px-2 py-1 text-xs">
                                        <select x-model="field.type" :name="`sections[${sIndex}][fields][${fIndex}][type]`"
                                                class="rounded border border-gray-300 px-2 py-1 text-xs">
                                            <option value="text">text</option>
                                            <option value="textarea">textarea</option>
                                            <option value="email">email</option>
                                            <option value="tel">tel</option>
                                            <option value="number">number</option>
                                            <option value="date">date</option>
                                            <option value="select">select</option>
                                            <option value="checkbox">checkbox</option>
                                            <option value="radio">radio</option>
                                            <option value="file">file</option>
                                            <option value="signature">signature</option>
                                        </select>
                                        <input type="text" x-model="field.label" :name="`sections[${sIndex}][fields][${fIndex}][label]`" placeholder="Label"
                                               class="rounded border border-gray-300 px-2 py-1 text-xs">
                                        <input type="text" x-model="field.placeholder" :name="`sections[${sIndex}][fields][${fIndex}][placeholder]`" placeholder="Placeholder"
                                               class="rounded border border-gray-300 px-2 py-1 text-xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <label class="inline-flex items-center gap-1 text-xs text-gray-700">
                                                <input type="hidden" :name="`sections[${sIndex}][fields][${fIndex}][required]`" value="0">
                                                <input type="checkbox" x-model="field.required" :name="`sections[${sIndex}][fields][${fIndex}][required]`" value="1" class="h-3.5 w-3.5 rounded border-gray-300">
                                                Required
                                            </label>
                                            <button type="button" @click="removeField(sIndex, fIndex)" class="text-xs font-medium text-red-600 hover:text-red-700">Remove</button>
                                        </div>

                                        <div class="md:col-span-5" x-show="field.type === 'select' || field.type === 'radio' || field.type === 'checkbox'">
                                            <input type="text" x-model="field.options_raw" :name="`sections[${sIndex}][fields][${fIndex}][options_raw]`"
                                                   placeholder="Options comma separated" class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-base font-semibold text-gray-900">Progression Rules (Optional)</h2>

            <template x-for="(rule, rIndex) in progressionRules" :key="rule._key">
                <div class="mb-2 grid grid-cols-1 gap-2 rounded-md border border-gray-200 p-3 md:grid-cols-5">
                    <input type="text" x-model="rule.from_section" :name="`progression_rules[${rIndex}][from_section]`" placeholder="from_section" class="rounded border border-gray-300 px-2 py-1 text-xs">
                    <input type="text" x-model="rule.to_section" :name="`progression_rules[${rIndex}][to_section]`" placeholder="to_section" class="rounded border border-gray-300 px-2 py-1 text-xs">
                    <input type="text" x-model="rule.condition_field" :name="`progression_rules[${rIndex}][condition_field]`" placeholder="condition_field" class="rounded border border-gray-300 px-2 py-1 text-xs">
                    <select x-model="rule.condition_operator" :name="`progression_rules[${rIndex}][condition_operator]`" class="rounded border border-gray-300 px-2 py-1 text-xs">
                        <option value="equals">equals</option>
                        <option value="not_equals">not_equals</option>
                        <option value="contains">contains</option>
                        <option value="in">in</option>
                    </select>
                    <div class="flex items-center gap-2">
                        <input type="text" x-model="rule.condition_value" :name="`progression_rules[${rIndex}][condition_value]`" placeholder="value" class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                        <button type="button" @click="removeRule(rIndex)" class="text-xs font-medium text-red-600">Remove</button>
                    </div>
                </div>
            </template>

            <button type="button" @click="addRule" class="rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                Add Progression Rule
            </button>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('crm.applications.forms.index') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $isEdit ? 'Update Template' : 'Create Template' }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            function applicationBuilder(initialSections, initialRules) {
                return {
                    requiredSectionIds: [
                        'personal_details',
                        'academic_history',
                        'entrance_exam_scores',
                        'co_curricular_activities',
                        'declarations',
                        'digital_signature',
                    ],
                    sections: (initialSections || []).map((section, idx) => ({
                        _key: `section-${idx}-${Date.now()}`,
                        id: section.id || '',
                        title: section.title || '',
                        order: section.order || (idx + 1),
                        description: section.description || '',
                        fields: (section.fields || []).map((field, fIdx) => ({
                            _key: `field-${idx}-${fIdx}-${Date.now()}`,
                            id: field.id || '',
                            type: field.type || 'text',
                            label: field.label || '',
                            required: Boolean(field.required),
                            placeholder: field.placeholder || '',
                            options_raw: (field.options || []).join(', '),
                        })),
                    })),
                    progressionRules: (initialRules || []).map((rule, idx) => ({
                        _key: `rule-${idx}-${Date.now()}`,
                        from_section: rule.from_section || '',
                        to_section: rule.to_section || '',
                        condition_field: rule.condition_field || '',
                        condition_operator: rule.condition_operator || 'equals',
                        condition_value: rule.condition_value || '',
                    })),
                    slug: '{{ old('slug', $template->slug ?? '') }}',
                    syncSlug(event) {
                        if (this.slug !== '') {
                            return;
                        }

                        this.slug = (event.target.value || '')
                            .toLowerCase()
                            .replace(/[^a-z0-9\s-]/g, '')
                            .trim()
                            .replace(/\s+/g, '-')
                            .replace(/-+/g, '-');
                    },
                    normalizedSectionIds() {
                        return this.sections
                            .map((section) => (section.id || '').trim())
                            .filter((sectionId) => sectionId !== '');
                    },
                    sectionExists(sectionId) {
                        return this.normalizedSectionIds().includes(sectionId);
                    },
                    hasDuplicateSectionIds() {
                        const ids = this.normalizedSectionIds();
                        return new Set(ids).size !== ids.length;
                    },
                    hasDigitalSignatureField() {
                        return this.sections.some((section) =>
                            (section.fields || []).some((field) => field.type === 'signature')
                        );
                    },
                    hasDuplicateFieldIds() {
                        return this.sections.some((section) => {
                            const fieldIds = (section.fields || [])
                                .map((field) => (field.id || '').trim())
                                .filter((fieldId) => fieldId !== '');

                            return new Set(fieldIds).size !== fieldIds.length;
                        });
                    },
                    ap002Ready() {
                        const missingRequiredSections = this.requiredSectionIds.filter(
                            (requiredSectionId) => !this.sectionExists(requiredSectionId)
                        );

                        return missingRequiredSections.length === 0
                            && this.hasDigitalSignatureField()
                            && !this.hasDuplicateSectionIds()
                            && !this.hasDuplicateFieldIds();
                    },
                    addSection() {
                        this.sections.push({
                            _key: `section-${Date.now()}-${Math.random()}`,
                            id: '',
                            title: '',
                            order: this.sections.length + 1,
                            description: '',
                            fields: [],
                        });
                    },
                    removeSection(index) {
                        this.sections.splice(index, 1);
                    },
                    addField(sectionIndex) {
                        this.sections[sectionIndex].fields.push({
                            _key: `field-${Date.now()}-${Math.random()}`,
                            id: '',
                            type: 'text',
                            label: '',
                            required: false,
                            placeholder: '',
                            options_raw: '',
                        });
                    },
                    removeField(sectionIndex, fieldIndex) {
                        this.sections[sectionIndex].fields.splice(fieldIndex, 1);
                    },
                    addRule() {
                        this.progressionRules.push({
                            _key: `rule-${Date.now()}-${Math.random()}`,
                            from_section: '',
                            to_section: '',
                            condition_field: '',
                            condition_operator: 'equals',
                            condition_value: '',
                        });
                    },
                    removeRule(index) {
                        this.progressionRules.splice(index, 1);
                    },
                };
            }
        </script>
    @endpush
</x-layouts.crm>
