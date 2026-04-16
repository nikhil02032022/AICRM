<x-layouts.crm>
    <x-slot:header>{{ $template->name }} — Application Fill</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ route('crm.applications.forms.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to Templates
        </a>
    </x-slot:headerActions>

    @php
        $sections = collect($template->sections ?? [])
            ->sortBy(static fn (array $section): int => (int) ($section['order'] ?? 999))
            ->values();

        $draftData = $draft?->form_data ?? [];
        $saveRoute = $draft
            ? route('crm.applications.drafts.save', $draft->uuid)
            : route('crm.applications.forms.fill.save', $template->uuid);
        $submitRoute = $draft
            ? route('crm.applications.drafts.submit', $draft->uuid)
            : route('crm.applications.forms.fill.save', $template->uuid);
        $modeLabel = $draft ? 'Resume Draft' : 'Start New Draft';

        $settings = $template->settings ?? [];
        $applicationFeeEnabled = (bool) ($settings['application_fee_enabled'] ?? false);
        $applicationFeeAmount = isset($settings['application_fee_amount']) ? number_format((float) $settings['application_fee_amount'], 2) : null;
        $applicationFeeCurrency = strtoupper((string) ($settings['application_fee_currency'] ?? 'INR'));
        $applicationFeeStatus = $draft?->application_fee_status ?? ($applicationFeeEnabled ? 'pending' : 'not_required');
        $allowMultiProgrammeApplications = (bool) ($settings['allow_multi_programme_applications'] ?? false);
        $maxProgrammesPerApplication = (int) ($settings['max_programmes_per_application'] ?? 1);
        $selectedProgrammeUuids = old('programme_uuids', is_array($draft?->selected_programme_uuids ?? null) ? $draft->selected_programme_uuids : []);
        $availableProgrammes = isset($availableProgrammes) ? $availableProgrammes : collect();
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-800">
            <p class="font-semibold">{{ $modeLabel }}</p>
            <p class="mt-1">Use Save Draft anytime. Submit requires meeting minimum completeness threshold configured for this template.</p>
            @if ($draft)
                <p class="mt-2 text-xs text-indigo-700">
                    Resume token: <span class="font-mono">{{ $draft->resume_token }}</span>
                    · Last saved {{ $draft->last_saved_at?->diffForHumans() ?? 'not available' }}
                </p>
            @endif
        </div>

        @if ($applicationFeeEnabled)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">Application Fee (AP-004)</p>
                        <p class="mt-1">{{ $applicationFeeCurrency }} {{ $applicationFeeAmount ?? '0.00' }} must be paid before final submission.</p>
                        <p class="mt-1 text-xs">Current status: <span class="font-semibold uppercase">{{ $applicationFeeStatus }}</span></p>
                    </div>

                    @if ($draft && $applicationFeeStatus !== 'paid')
                        <form method="POST" action="{{ route('crm.applications.drafts.pay-fee', $draft->uuid) }}">
                            @csrf
                            <input type="hidden" name="gateway" value="online">
                            <button
                                type="submit"
                                class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700"
                            >
                                Pay Fee Now
                            </button>
                        </form>
                    @endif
                </div>

                @if (! $draft)
                    <p class="mt-2 text-xs">Create a draft first, then use the pay action from the resume page.</p>
                @endif
            </div>
        @endif

        @if ($allowMultiProgrammeApplications || $maxProgrammesPerApplication > 1)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                <p class="font-semibold">Programme Selection (AP-005)</p>
                <p class="mt-1 text-xs">Select up to {{ max(2, $maxProgrammesPerApplication) }} programmes for simultaneous application submission.</p>
            </div>
        @endif

        <form method="POST" action="{{ $saveRoute }}" class="space-y-6" id="application-fill-form">
            @csrf

            <input type="hidden" name="current_section_id" value="{{ $sections->first()['id'] ?? '' }}">
            <input type="hidden" name="last_completed_section_order" value="{{ (int) ($sections->last()['order'] ?? 1) }}">
            <input type="hidden" name="progress_percentage" value="{{ old('progress_percentage', $draft?->progress_percentage ?? 0) }}">

            @if ($allowMultiProgrammeApplications || $maxProgrammesPerApplication > 1)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-gray-900">Select Programmes</h2>
                    <p class="mt-1 text-xs text-gray-600">You can select multiple programmes in one application as allowed by template settings.</p>

                    <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                        @foreach ($availableProgrammes as $programme)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox"
                                       name="programme_uuids[]"
                                       value="{{ $programme->erp_programme_uuid }}"
                                       @checked(in_array((string) $programme->erp_programme_uuid, $selectedProgrammeUuids, true))
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>{{ $programme->name }} @if($programme->code) ({{ $programme->code }}) @endif</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($sections as $section)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-base font-semibold text-gray-900">{{ $section['title'] ?? 'Untitled Section' }}</h2>
                        @if (!empty($section['description']))
                            <p class="mt-1 text-sm text-gray-600">{{ $section['description'] }}</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach (($section['fields'] ?? []) as $field)
                            @php
                                $sectionId = (string) ($section['id'] ?? 'section');
                                $fieldId = (string) ($field['id'] ?? 'field');
                                $fieldLabel = (string) ($field['label'] ?? ucfirst($fieldId));
                                $fieldType = (string) ($field['type'] ?? 'text');
                                $fieldRequired = (bool) ($field['required'] ?? false);
                                $fieldPlaceholder = (string) ($field['placeholder'] ?? '');
                                $existingValue = old(
                                    "form_data.$sectionId.$fieldId",
                                    $draftData[$sectionId][$fieldId] ?? null,
                                );
                                $options = $field['options'] ?? [];
                            @endphp

                            <div class="{{ $fieldType === 'textarea' ? 'md:col-span-2' : '' }}">
                                <label for="field-{{ $sectionId }}-{{ $fieldId }}" class="mb-1 block text-sm font-medium text-gray-700">
                                    {{ $fieldLabel }}
                                    @if($fieldRequired)
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>

                                @if(in_array($fieldType, ['text', 'email', 'tel', 'number', 'date'], true))
                                    <input
                                        id="field-{{ $sectionId }}-{{ $fieldId }}"
                                        type="{{ $fieldType }}"
                                        name="form_data[{{ $sectionId }}][{{ $fieldId }}]"
                                        value="{{ is_scalar($existingValue) ? (string) $existingValue : '' }}"
                                        @if($fieldRequired) required @endif
                                        placeholder="{{ $fieldPlaceholder }}"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    >
                                @elseif($fieldType === 'textarea')
                                    <textarea
                                        id="field-{{ $sectionId }}-{{ $fieldId }}"
                                        name="form_data[{{ $sectionId }}][{{ $fieldId }}]"
                                        rows="3"
                                        @if($fieldRequired) required @endif
                                        placeholder="{{ $fieldPlaceholder }}"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    >{{ is_scalar($existingValue) ? (string) $existingValue : '' }}</textarea>
                                @elseif($fieldType === 'checkbox')
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="hidden"
                                            name="form_data[{{ $sectionId }}][{{ $fieldId }}]"
                                            value="0"
                                        >
                                        <input
                                            id="field-{{ $sectionId }}-{{ $fieldId }}"
                                            type="checkbox"
                                            name="form_data[{{ $sectionId }}][{{ $fieldId }}]"
                                            value="1"
                                            @checked((bool) $existingValue)
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        {{ $fieldLabel }}
                                    </label>
                                @elseif($fieldType === 'select')
                                    <select
                                        id="field-{{ $sectionId }}-{{ $fieldId }}"
                                        name="form_data[{{ $sectionId }}][{{ $fieldId }}]"
                                        @if($fieldRequired) required @endif
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    >
                                        <option value="">Select {{ $fieldLabel }}</option>
                                        @foreach ($options as $option)
                                            @php
                                                $optionValue = is_array($option) ? (string) ($option['value'] ?? $option['label'] ?? '') : (string) $option;
                                                $optionLabel = is_array($option) ? (string) ($option['label'] ?? $optionValue) : (string) $option;
                                            @endphp
                                            <option value="{{ $optionValue }}" @selected((string) $existingValue === $optionValue)>
                                                {{ $optionLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input
                                        id="field-{{ $sectionId }}-{{ $fieldId }}"
                                        type="text"
                                        name="form_data[{{ $sectionId }}][{{ $fieldId }}]"
                                        value="{{ is_scalar($existingValue) ? (string) $existingValue : '' }}"
                                        @if($fieldRequired) required @endif
                                        placeholder="{{ $fieldPlaceholder !== '' ? $fieldPlaceholder : 'Enter value' }}"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    >
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex items-center justify-end gap-3">
                <button
                    type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Save Draft
                </button>

                <button
                    type="submit"
                    formaction="{{ $submitRoute }}"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Submit Application
                </button>
            </div>
        </form>
    </div>
</x-layouts.crm>
