<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->name }} — Enquiry Form</title>
    <meta name="description" content="Fill in your details to submit your enquiry.">
    {{-- BRD: CRM-LC-001 — Public form — no CRM chrome, minimal styles --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/public.js'])
    @if($form->accent_color)
    <style>
        :root { --form-accent: {{ e($form->accent_color) }}; }
        .btn-accent { background-color: var(--form-accent); }
        .btn-accent:hover { filter: brightness(0.92); }
        .ring-accent:focus { --tw-ring-color: var(--form-accent); }
    </style>
    @endif
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-violet-50 font-sans antialiased {{ ($previewMode ?? false) ? 'pt-12' : '' }}">

    {{-- Preview Mode Banner --}}
    @if($previewMode ?? false)
    <div class="fixed inset-x-0 top-0 z-50 flex items-center justify-center gap-2.5 bg-amber-400 px-4 py-2.5 text-sm font-semibold text-amber-900 shadow-md" role="status" aria-live="polite">
        <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
        </svg>
        PREVIEW MODE &mdash; This form is not published. Submissions are disabled.
        <button onclick="window.close()" class="ml-4 rounded border border-amber-700/30 bg-amber-500/40 px-2.5 py-0.5 text-xs font-medium hover:bg-amber-500/60 transition-colors" aria-label="Close preview">
            Close
        </button>
    </div>
    @endif

    <div class="flex min-h-screen items-start justify-center px-4 py-12 sm:py-16">
        <div class="w-full max-w-lg">

            {{-- Logo / Institution branding --}}
            <div class="mb-6 text-center">
                @if($form->logo_url)
                    <img src="{{ e($form->logo_url) }}" alt="Institution logo" class="mx-auto mb-4 h-12 object-contain" loading="lazy">
                @else
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                        </svg>
                    </div>
                @endif
                <h1 class="text-xl font-bold text-gray-900">{{ $form->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Fill in your details and we'll get back to you.</p>
            </div>

            {{-- Form card --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-xl sm:p-8"
                 x-data="publicForm(@json($form->fields ?? []))">

                {{-- Success state --}}
                <div x-show="submitted" class="text-center py-8" x-transition style="display:none">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Thank you!</h2>
                    <p class="mt-2 text-sm text-gray-600">Your enquiry has been received. We'll contact you shortly.</p>
                </div>

                {{-- Form --}}
                <form x-show="!submitted" @submit.prevent="submitForm" novalidate aria-label="Enquiry form">

                    {{-- General error --}}
                    <div x-show="errorMessage" x-transition
                         class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                         role="alert" style="display:none">
                        <span x-text="errorMessage"></span>
                    </div>

                    <div class="space-y-4">

                        {{-- Always-present core fields --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="first_name" class="mb-1.5 block text-sm font-medium text-gray-700">
                                    First Name <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="first_name" x-model="formData.first_name"
                                       required maxlength="80" autocomplete="given-name"
                                       class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                       :class="{ 'border-red-500': errors.first_name }"
                                       placeholder="Arjun"
                                       aria-required="true">
                                <p x-show="errors.first_name" class="mt-1 text-xs text-red-600" x-text="errors.first_name" role="alert"></p>
                            </div>
                            <div>
                                <label for="last_name" class="mb-1.5 block text-sm font-medium text-gray-700">
                                    Last Name <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="last_name" x-model="formData.last_name"
                                       required maxlength="80" autocomplete="family-name"
                                       class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                       :class="{ 'border-red-500': errors.last_name }"
                                       placeholder="Sharma"
                                       aria-required="true">
                                <p x-show="errors.last_name" class="mt-1 text-xs text-red-600" x-text="errors.last_name" role="alert"></p>
                            </div>
                        </div>

                        <div>
                            <label for="mobile" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Mobile Number <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <div class="flex rounded-lg border border-gray-300 shadow-sm overflow-hidden focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
                                 :class="{ 'border-red-500': errors.mobile }">
                                <span class="flex-shrink-0 border-r border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-medium">+91</span>
                                <input type="tel" id="mobile" x-model="formData.mobile"
                                       required maxlength="10" pattern="[6-9]\d{9}" autocomplete="tel"
                                       inputmode="numeric"
                                       class="block flex-1 bg-transparent px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none"
                                       placeholder="9876543210"
                                       aria-required="true">
                            </div>
                            <p x-show="errors.mobile" class="mt-1 text-xs text-red-600" x-text="errors.mobile" role="alert"></p>
                        </div>

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" id="email" x-model="formData.email"
                                   maxlength="160" autocomplete="email"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                   :class="{ 'border-red-500': errors.email }"
                                   placeholder="arjun@example.com">
                            <p x-show="errors.email" class="mt-1 text-xs text-red-600" x-text="errors.email" role="alert"></p>
                        </div>

                        {{-- BRD: CRM-LC-002 — Dynamic conditional fields from form schema --}}
                        <template x-for="field in visibleFields" :key="field.id">
                            <div>
                                <label :for="'field-' + field.id" class="mb-1.5 block text-sm font-medium text-gray-700">
                                    <span x-text="field.label"></span>
                                    <span x-show="field.required" class="text-red-500" aria-hidden="true"> *</span>
                                </label>

                                {{-- text / tel / email / hidden --}}
                                <template x-if="['text','tel','email','hidden'].includes(field.type)">
                                    <input :type="field.type" :id="'field-' + field.id"
                                           x-model="formData[field.id]"
                                           :required="field.required"
                                           :placeholder="field.placeholder ?? ''"
                                           class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                           :class="{ 'border-red-500': errors[field.id] }">
                                </template>

                                {{-- select --}}
                                <template x-if="field.type === 'select'">
                                    <select :id="'field-' + field.id"
                                            x-model="formData[field.id]"
                                            :required="field.required"
                                            class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                            :class="{ 'border-red-500': errors[field.id] }">
                                        <option value="">Select…</option>
                                        <template x-for="opt in (field.options ?? [])" :key="opt">
                                            <option :value="opt" x-text="opt"></option>
                                        </template>
                                    </select>
                                </template>

                                {{-- textarea --}}
                                <template x-if="field.type === 'textarea'">
                                    <textarea :id="'field-' + field.id"
                                              x-model="formData[field.id]"
                                              :required="field.required"
                                              rows="3"
                                              :placeholder="field.placeholder ?? ''"
                                              class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"
                                              :class="{ 'border-red-500': errors[field.id] }"></textarea>
                                </template>

                                {{-- checkbox --}}
                                <template x-if="field.type === 'checkbox'">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" :id="'field-' + field.id"
                                               x-model="formData[field.id]"
                                               :required="field.required"
                                               class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <label :for="'field-' + field.id" class="text-sm text-gray-700 cursor-pointer" x-text="field.label"></label>
                                    </div>
                                </template>

                                <p x-show="errors[field.id]" class="mt-1 text-xs text-red-600" :x-text="errors[field.id]" role="alert"></p>
                            </div>
                        </template>

                        {{-- BRD: CRM-CR-001 — Mandatory consent checkbox --}}
                        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" id="consent_given" x-model="formData.consent_given"
                                       required
                                       class="mt-0.5 h-4 w-4 flex-shrink-0 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                       :class="{ 'border-red-500': errors.consent_given }"
                                       aria-required="true">
                                <label for="consent_given" class="cursor-pointer text-xs text-gray-600 leading-relaxed">
                                    <span class="font-medium text-gray-900">I consent to the collection and processing of my personal information</span>
                                    for the purpose of admissions enquiry. My data will be handled as per the
                                    <a href="#" class="text-indigo-600 hover:underline">Privacy Policy</a>.
                                    <span class="text-red-500" aria-hidden="true"> *</span>
                                </label>
                            </div>
                            <p x-show="errors.consent_given" class="mt-1.5 ml-7 text-xs text-red-600" x-text="errors.consent_given" role="alert"></p>
                        </div>

                        {{-- Hidden fields: UTM params (LC-015) + metadata --}}
                        <input type="hidden" x-model="formData.consent_form_version" name="consent_form_version">
                    </div>

                    @if($previewMode ?? false)
                    <div class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        Submissions are disabled in preview mode.
                    </div>
                    @else
                    <button type="submit"
                            :disabled="submitting"
                            class="mt-6 w-full cursor-pointer rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-md transition-all hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed"
                            :class="{ 'btn-accent': true }"
                            aria-live="polite">
                        <span x-show="!submitting">Submit Enquiry</span>
                        <span x-show="submitting" class="flex items-center justify-center gap-2" style="display:none">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting…
                        </span>
                    </button>
                    @endif
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                Powered by A2A CRM &mdash; DPDP compliant
            </p>
        </div>
    </div>

    <script>
    function publicForm(schemaFields) {
        return {
            schemaFields: schemaFields,
            formData: {
                first_name: '',
                last_name: '',
                mobile: '',
                email: '',
                consent_given: false,
                // BRD: CRM-CR-002 — consent form version from form config
                consent_form_version: '{{ e($form->consent_form_version) }}',
                // BRD: CRM-LC-015 — UTM params populated from URL on x-init
                source_utm_params: {},
            },
            errors: {},
            errorMessage: '',
            submitting: false,
            submitted: false,

            // BRD: CRM-LC-015 — auto-capture UTM params from URL on Alpine x-init
            init() {
                const params = new URLSearchParams(window.location.search);
                ['utm_source','utm_medium','utm_campaign','utm_term','utm_content'].forEach(k => {
                    if (params.get(k)) this.formData.source_utm_params[k] = params.get(k);
                });
            },

            // BRD: CRM-LC-002 — computed visible fields based on show_if rules
            get visibleFields() {
                return this.schemaFields.filter(field => {
                    if (!field.show_if || !field.show_if.field) return true;
                    const si = field.show_if;
                    const val = String(this.formData[si.field] ?? '');
                    switch (si.operator) {
                        case 'equals':     return val === si.value;
                        case 'not_equals': return val !== si.value;
                        case 'contains':   return val.includes(si.value);
                        default:           return true;
                    }
                });
            },

            async submitForm() {
                this.submitting = true;
                this.errors = {};
                this.errorMessage = '';

                // Build payload
                const payload = { ...this.formData };
                // Dynamic fields
                this.visibleFields.forEach(f => {
                    if (this.formData[f.id] !== undefined) {
                        payload[f.id] = this.formData[f.id];
                    }
                });

                try {
                    const res = await fetch('{{ route('public.form.submit', $form->slug) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        this.submitted = true;
                        @if($form->redirect_url)
                        setTimeout(() => { window.location.href = '{{ e($form->redirect_url) }}'; }, 2000);
                        @endif
                    } else if (res.status === 422 && data.errors) {
                        this.errors = Object.fromEntries(
                            Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
                        );
                        this.errorMessage = 'Please correct the errors above.';
                    } else {
                        this.errorMessage = data.message ?? 'Something went wrong. Please try again.';
                    }
                } catch (e) {
                    this.errorMessage = 'Network error. Please check your connection and try again.';
                } finally {
                    this.submitting = false;
                }
            }
        };
    }
    </script>
</body>
</html>
