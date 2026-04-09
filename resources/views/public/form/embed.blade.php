{{-- BRD: CRM-LC-001 — iFrame embed version — uses bare embed layout, no nav/header --}}
<x-layouts.embed>
    <x-slot:title>{{ $form->name }}</x-slot:title>

    <div class="min-h-screen bg-white px-4 py-8 font-sans antialiased"
         x-data="publicForm(@json($form->fields ?? []))">

        @if($form->logo_url)
        <div class="mb-4 text-center">
            <img src="{{ e($form->logo_url) }}" alt="Logo" class="mx-auto h-10 object-contain" loading="lazy">
        </div>
        @endif

        <h1 class="mb-5 text-center text-lg font-bold text-gray-900">{{ $form->name }}</h1>

        {{-- Success --}}
        <div x-show="submitted" class="text-center py-6" x-transition style="display:none">
            <svg class="mx-auto mb-3 h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <p class="text-sm font-semibold text-gray-800">Thank you! We'll be in touch soon.</p>
        </div>

        {{-- Form --}}
        <form x-show="!submitted" @submit.prevent="submitForm" novalidate class="space-y-4" aria-label="Enquiry form">

            <div x-show="errorMessage" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-xs text-red-700" role="alert" style="display:none">
                <span x-text="errorMessage"></span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="ei_first_name" class="mb-1 block text-xs font-medium text-gray-700">First Name *</label>
                    <input type="text" id="ei_first_name" x-model="formData.first_name" required maxlength="80"
                           autocomplete="given-name" placeholder="Arjun"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                           :class="{ 'border-red-500': errors.first_name }">
                    <p x-show="errors.first_name" class="mt-0.5 text-xs text-red-600" x-text="errors.first_name" role="alert"></p>
                </div>
                <div>
                    <label for="ei_last_name" class="mb-1 block text-xs font-medium text-gray-700">Last Name *</label>
                    <input type="text" id="ei_last_name" x-model="formData.last_name" required maxlength="80"
                           autocomplete="family-name" placeholder="Sharma"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                           :class="{ 'border-red-500': errors.last_name }">
                    <p x-show="errors.last_name" class="mt-0.5 text-xs text-red-600" x-text="errors.last_name" role="alert"></p>
                </div>
            </div>

            <div>
                <label for="ei_mobile" class="mb-1 block text-xs font-medium text-gray-700">Mobile *</label>
                <input type="tel" id="ei_mobile" x-model="formData.mobile" required maxlength="10"
                       pattern="[6-9]\d{9}" inputmode="numeric" autocomplete="tel" placeholder="9876543210"
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                       :class="{ 'border-red-500': errors.mobile }">
                <p x-show="errors.mobile" class="mt-0.5 text-xs text-red-600" x-text="errors.mobile" role="alert"></p>
            </div>

            <div>
                <label for="ei_email" class="mb-1 block text-xs font-medium text-gray-700">Email</label>
                <input type="email" id="ei_email" x-model="formData.email" maxlength="160"
                       autocomplete="email" placeholder="you@example.com"
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

            <template x-for="field in visibleFields" :key="field.id">
                <div>
                    <label :for="'ei-' + field.id" class="mb-1 block text-xs font-medium text-gray-700">
                        <span x-text="field.label"></span><span x-show="field.required" class="text-red-500"> *</span>
                    </label>
                    <template x-if="['text','tel','email'].includes(field.type)">
                        <input :type="field.type" :id="'ei-' + field.id" x-model="formData[field.id]"
                               :required="field.required" :placeholder="field.placeholder ?? ''"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </template>
                    <template x-if="field.type === 'select'">
                        <select :id="'ei-' + field.id" x-model="formData[field.id]" :required="field.required"
                                class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">Select…</option>
                            <template x-for="opt in (field.options ?? [])" :key="opt">
                                <option :value="opt" x-text="opt"></option>
                            </template>
                        </select>
                    </template>
                </div>
            </template>

            <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-3">
                <label class="flex cursor-pointer items-start gap-2 text-xs text-gray-600">
                    <input type="checkbox" id="ei_consent" x-model="formData.consent_given" required
                           class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           :class="{ 'border-red-500': errors.consent_given }">
                    <span>I consent to the collection and processing of my personal information for admissions enquiry purposes. <span class="text-red-500">*</span></span>
                </label>
                <p x-show="errors.consent_given" class="mt-1 text-xs text-red-600" x-text="errors.consent_given" role="alert"></p>
            </div>

            <button type="submit" :disabled="submitting"
                    class="w-full cursor-pointer rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!submitting">Submit Enquiry</span>
                <span x-show="submitting" style="display:none">Submitting…</span>
            </button>
        </form>
    </div>

    <script>
    function publicForm(schemaFields) {
        return {
            schemaFields: schemaFields,
            formData: {
                first_name: '', last_name: '', mobile: '', email: '',
                consent_given: false,
                consent_form_version: '{{ e($form->consent_form_version) }}',
                source_utm_params: {},
            },
            errors: {}, errorMessage: '', submitting: false, submitted: false,
            init() {
                const p = new URLSearchParams(window.location.search);
                ['utm_source','utm_medium','utm_campaign','utm_term','utm_content'].forEach(k => {
                    if (p.get(k)) this.formData.source_utm_params[k] = p.get(k);
                });
            },
            get visibleFields() {
                return this.schemaFields.filter(f => {
                    if (!f.show_if?.field) return true;
                    const val = String(this.formData[f.show_if.field] ?? '');
                    if (f.show_if.operator === 'equals') return val === f.show_if.value;
                    if (f.show_if.operator === 'not_equals') return val !== f.show_if.value;
                    if (f.show_if.operator === 'contains') return val.includes(f.show_if.value);
                    return true;
                });
            },
            async submitForm() {
                this.submitting = true; this.errors = {}; this.errorMessage = '';
                const payload = { ...this.formData };
                this.visibleFields.forEach(f => { if (this.formData[f.id] !== undefined) payload[f.id] = this.formData[f.id]; });
                try {
                    const res = await fetch('{{ route('public.form.submit', $form->slug) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json();
                    if (res.ok && data.success) { this.submitted = true; }
                    else if (res.status === 422 && data.errors) {
                        this.errors = Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, Array.isArray(v) ? v[0] : v]));
                        this.errorMessage = 'Please fix the errors above.';
                    } else { this.errorMessage = data.message ?? 'Something went wrong.'; }
                } catch { this.errorMessage = 'Network error. Try again.'; }
                finally { this.submitting = false; }
            }
        };
    }
    </script>
</x-layouts.embed>
