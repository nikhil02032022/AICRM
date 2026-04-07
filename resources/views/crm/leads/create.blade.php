<x-layouts.crm title="New Lead">
    <div class="mx-auto max-w-2xl space-y-6">
        {{-- Breadcrumb --}}
        <nav class="flex text-sm text-gray-500 gap-1.5 items-center">
            <a href="{{ route('crm.leads.index') }}" class="hover:text-indigo-600">Leads</a>
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">New Lead</span>
        </nav>

        {{-- Form card --}}
        <div class="card">
            <h1 class="text-xl font-bold text-gray-900 mb-1">Create New Lead</h1>
            <p class="text-sm text-gray-500 mb-6">
                All fields marked <span class="text-red-500">*</span> are required.
                Consent must be confirmed before saving.
            </p>

            <form
                id="create-lead-form"
                method="POST"
                action="{{ route('api.crm.leads.store') }}"
                x-data="leadCreateForm()"
                @submit.prevent="submit"
                class="space-y-5"
            >
                @csrf

                {{-- Name row --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="first_name">First Name <span class="text-red-500">*</span></label>
                        <input id="first_name" name="first_name" type="text" x-model="form.first_name"
                               class="input-field" placeholder="Arjun" autocomplete="given-name" required>
                        <p x-show="errors.first_name" x-text="errors.first_name" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="label" for="last_name">Last Name <span class="text-red-500">*</span></label>
                        <input id="last_name" name="last_name" type="text" x-model="form.last_name"
                               class="input-field" placeholder="Sharma" autocomplete="family-name" required>
                        <p x-show="errors.last_name" x-text="errors.last_name" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>

                {{-- Contact --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="mobile">Mobile <span class="text-red-500">*</span></label>
                        <input id="mobile" name="mobile" type="tel" x-model="form.mobile"
                               class="input-field" placeholder="9876543210" pattern="[6-9][0-9]{9}" maxlength="10" required>
                        <p class="mt-1 text-xs text-gray-400">10-digit Indian mobile number</p>
                        <p x-show="errors.mobile" x-text="errors.mobile" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="label" for="email">Email</label>
                        <input id="email" name="email" type="email" x-model="form.email"
                               class="input-field" placeholder="arjun@example.com" autocomplete="email">
                        <p x-show="errors.email" x-text="errors.email" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>

                {{-- Source — BRD: CRM-LC-014 mandatory --}}
                <div>
                    <label class="label" for="source">Lead Source <span class="text-red-500">*</span></label>
                    <select id="source" name="source" x-model="form.source" class="input-field" required>
                        <option value="">— Select Source —</option>
                        @foreach($sourceOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p x-show="errors.source" x-text="errors.source" class="mt-1 text-xs text-red-600"></p>
                </div>

                {{-- UTM params (shown only for digital sources) --}}
                <div x-show="['google_ads','facebook','website_organic'].includes(form.source)"
                     x-transition:enter="transition ease-out duration-150"
                     class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-4 space-y-3">
                    <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">UTM Parameters</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label text-xs" for="utm_source">utm_source</label>
                            <input id="utm_source" type="text" x-model="form.source_utm_params.utm_source" class="input-field text-sm" placeholder="google">
                        </div>
                        <div>
                            <label class="label text-xs" for="utm_medium">utm_medium</label>
                            <input id="utm_medium" type="text" x-model="form.source_utm_params.utm_medium" class="input-field text-sm" placeholder="cpc">
                        </div>
                        <div class="col-span-2">
                            <label class="label text-xs" for="utm_campaign">utm_campaign</label>
                            <input id="utm_campaign" type="text" x-model="form.source_utm_params.utm_campaign" class="input-field text-sm" placeholder="mba-2026">
                        </div>
                    </div>
                </div>

                {{-- Location --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="city">City</label>
                        <input id="city" name="city" type="text" x-model="form.city" class="input-field" placeholder="Bengaluru">
                    </div>
                    <div>
                        <label class="label" for="state">State</label>
                        <input id="state" name="state" type="text" x-model="form.state" class="input-field" placeholder="Karnataka">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" x-model="form.notes" rows="3"
                              class="input-field resize-none" placeholder="Any relevant details about this lead…" maxlength="1000"></textarea>
                </div>

                {{-- DPDP Consent — BRD: CRM-CR-001 mandatory --}}
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" x-model="form.consent_given" name="consent_given"
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" required>
                        <span class="text-sm text-gray-700">
                            <strong class="text-gray-900">I confirm that</strong> the prospective student has given explicit consent
                            to be contacted by our institution for admission-related communication, in compliance with
                            the <strong>DPDP Act 2023</strong>. <span class="text-red-500">*</span>
                        </span>
                    </label>
                    <input type="hidden" name="consent_form_version" value="v1.0-2026-04">
                    <p x-show="errors.consent_given" x-text="errors.consent_given" class="mt-2 text-xs text-red-600"></p>
                </div>

                {{-- Error banner --}}
                <div x-show="globalError" x-text="globalError"
                     class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('crm.leads.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary" :disabled="submitting" x-text="submitting ? 'Saving…' : 'Create Lead'"></button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    function leadCreateForm() {
        return {
            submitting: false,
            globalError: '',
            errors: {},
            form: {
                first_name: '',
                last_name: '',
                mobile: '',
                email: '',
                source: '',
                source_utm_params: { utm_source: '', utm_medium: '', utm_campaign: '' },
                city: '',
                state: '',
                notes: '',
                consent_given: false,
                consent_form_version: 'v1.0-2026-04',
            },

            async submit() {
                this.submitting  = true;
                this.errors      = {};
                this.globalError = '';

                const payload = { ...this.form };
                // Only send UTM if source supports it
                if (!['google_ads','facebook','website_organic'].includes(this.form.source)) {
                    delete payload.source_utm_params;
                }

                try {
                    const res = await fetch('{{ route('api.crm.leads.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });

                    const json = await res.json();

                    if (res.status === 201) {
                        window.location.href = '{{ route('crm.leads.index') }}';
                        return;
                    }

                    if (res.status === 422 && json.errors) {
                        this.errors = Object.fromEntries(
                            Object.entries(json.errors).map(([k, v]) => [k, v[0]])
                        );
                    } else {
                        this.globalError = json.error?.message ?? 'An unexpected error occurred.';
                    }
                } catch {
                    this.globalError = 'Network error. Please try again.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
    </script>
    @endpush
</x-layouts.crm>
