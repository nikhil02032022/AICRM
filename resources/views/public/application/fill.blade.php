<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $template->name }} — Application Form</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 font-sans antialiased">
    @php
        $sections = collect($template->sections ?? [])
            ->sortBy(static fn (array $section): int => (int) ($section['order'] ?? 999))
            ->values();
        $draftData = $draft?->form_data ?? [];

        $saveAction = $draft
            ? route('public.application.resume.save', $draft->resume_token)
            : route('public.application.save', $template->slug);
        $submitAction = $draft
            ? route('public.application.resume.submit', $draft->resume_token)
            : null;
        $payFeeAction = $draft
            ? route('public.application.resume.pay-fee', $draft->resume_token)
            : null;

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

    <div class="mx-auto w-full max-w-4xl px-4 py-10">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-slate-900">{{ $template->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">Fill your application, save progress, and resume later with your secure link.</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($draft)
            <div class="mb-5 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-xs text-indigo-800">
                <p>Resume token: <span class="font-mono">{{ $draft->resume_token }}</span></p>
                <p class="mt-1">Last saved: {{ $draft->last_saved_at?->diffForHumans() ?? 'not available' }}</p>
            </div>
        @endif

        @if ($applicationFeeEnabled)
            <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Application Fee (AP-004)</p>
                <p class="mt-1">{{ $applicationFeeCurrency }} {{ $applicationFeeAmount ?? '0.00' }} is required before final submission.</p>
                <p class="mt-1 text-xs">Current status: <span class="font-semibold uppercase">{{ $applicationFeeStatus }}</span></p>

                @if($draft && $applicationFeeStatus !== 'paid' && $payFeeAction)
                    <form method="POST" action="{{ $payFeeAction }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="gateway" value="online">
                        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700">Pay Fee Now</button>
                    </form>
                @elseif(! $draft)
                    <p class="mt-2 text-xs">Save draft once to enable fee payment and secure resume link.</p>
                @endif
            </div>
        @endif

        @if ($allowMultiProgrammeApplications || $maxProgrammesPerApplication > 1)
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                <p class="font-semibold">Programme Selection (AP-005)</p>
                <p class="mt-1 text-xs">Select up to {{ max(2, $maxProgrammesPerApplication) }} programmes in this application.</p>
            </div>
        @endif

        <form method="POST" action="{{ $saveAction }}" class="space-y-5">
            @csrf
            <input type="hidden" name="current_section_id" value="{{ $sections->first()['id'] ?? '' }}">
            <input type="hidden" name="last_completed_section_order" value="{{ (int) ($sections->last()['order'] ?? 1) }}">
            <input type="hidden" name="progress_percentage" value="{{ old('progress_percentage', $draft?->progress_percentage ?? 0) }}">

            @if ($allowMultiProgrammeApplications || $maxProgrammesPerApplication > 1)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Select Programmes</h2>
                    <p class="mt-1 text-xs text-slate-600">You may apply to multiple programmes together as configured by this institution.</p>

                    <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                        @foreach ($availableProgrammes as $programme)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox"
                                       name="programme_uuids[]"
                                       value="{{ $programme->erp_programme_uuid }}"
                                       @checked(in_array((string) $programme->erp_programme_uuid, $selectedProgrammeUuids, true))
                                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>{{ $programme->name }} @if($programme->code) ({{ $programme->code }}) @endif</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($sections as $section)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">{{ $section['title'] ?? 'Section' }}</h2>
                    @if(!empty($section['description']))
                        <p class="mt-1 text-sm text-slate-600">{{ $section['description'] }}</p>
                    @endif

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach (($section['fields'] ?? []) as $field)
                            @php
                                $sectionId = (string) ($section['id'] ?? 'section');
                                $fieldId = (string) ($field['id'] ?? 'field');
                                $fieldLabel = (string) ($field['label'] ?? ucfirst($fieldId));
                                $fieldType = (string) ($field['type'] ?? 'text');
                                $fieldRequired = (bool) ($field['required'] ?? false);
                                $existingValue = old("form_data.$sectionId.$fieldId", $draftData[$sectionId][$fieldId] ?? null);
                            @endphp

                            <div class="{{ $fieldType === 'textarea' ? 'md:col-span-2' : '' }}">
                                <label for="{{ $sectionId }}-{{ $fieldId }}" class="mb-1 block text-sm font-medium text-slate-700">
                                    {{ $fieldLabel }} @if($fieldRequired)<span class="text-red-600">*</span>@endif
                                </label>

                                @if(in_array($fieldType, ['text','email','tel','number','date'], true))
                                    <input id="{{ $sectionId }}-{{ $fieldId }}" type="{{ $fieldType }}" name="form_data[{{ $sectionId }}][{{ $fieldId }}]" value="{{ is_scalar($existingValue) ? (string) $existingValue : '' }}" @if($fieldRequired) required @endif class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                @elseif($fieldType === 'textarea')
                                    <textarea id="{{ $sectionId }}-{{ $fieldId }}" name="form_data[{{ $sectionId }}][{{ $fieldId }}]" rows="3" @if($fieldRequired) required @endif class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ is_scalar($existingValue) ? (string) $existingValue : '' }}</textarea>
                                @elseif($fieldType === 'checkbox')
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="hidden" name="form_data[{{ $sectionId }}][{{ $fieldId }}]" value="0">
                                        <input id="{{ $sectionId }}-{{ $fieldId }}" type="checkbox" name="form_data[{{ $sectionId }}][{{ $fieldId }}]" value="1" @checked((bool)$existingValue) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ $fieldLabel }}
                                    </label>
                                @else
                                    <input id="{{ $sectionId }}-{{ $fieldId }}" type="text" name="form_data[{{ $sectionId }}][{{ $fieldId }}]" value="{{ is_scalar($existingValue) ? (string) $existingValue : '' }}" @if($fieldRequired) required @endif class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Save Progress</button>

                @if($draft && $submitAction)
                    <button type="submit" formaction="{{ $submitAction }}" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Submit Application</button>
                @endif
            </div>
        </form>
    </div>
</body>
</html>
