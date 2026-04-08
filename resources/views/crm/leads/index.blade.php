<x-layouts.crm title="Leads">
    <div class="space-y-6" x-data="leadModal()">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Lead Management</h1>
                <p class="mt-1 text-sm text-gray-500">All prospective student enquiries · AI scoring active</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-secondary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import
                </button>
                @can('crm.leads.create')
                <button
                    type="button"
                    class="btn-primary"
                    @click="open = true"
                    aria-haspopup="dialog"
                    aria-controls="new-lead-modal"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Lead
                </button>
                @endcan
            </div>
        </div>

        {{-- Success toast (fired after modal save) --}}
        <div
            x-show="toastVisible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            role="alert"
            aria-live="polite"
            class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800 shadow-sm"
            style="display:none"
        >
            <svg class="h-5 w-5 flex-shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <span class="flex-1 font-medium">Lead created successfully.</span>
            <button type="button" @click="toastVisible = false" aria-label="Dismiss" class="cursor-pointer text-green-600 transition-colors hover:text-green-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Live lead table --}}
        @livewire('crm.lead.lead-table')

        {{-- ===== New Lead Modal ===== --}}
        @can('crm.leads.create')

        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm"
            @click="closeModal()"
            aria-hidden="true"
            style="display:none"
        ></div>

        {{-- Dialog panel --}}
        <div
            id="new-lead-modal"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
            @keydown.escape.window="closeModal()"
            style="display:none"
        >
            <div class="relative my-auto w-full max-w-2xl rounded-xl bg-white shadow-2xl" @click.stop>

                {{-- Modal header --}}
                <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <h2 id="modal-title" class="text-lg font-semibold text-gray-900">Create New Lead</h2>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Fields marked <span class="text-red-500">*</span> are required. Consent must be confirmed before saving.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeModal()"
                        aria-label="Close modal"
                        class="ml-4 flex-shrink-0 rounded-md p-1.5 text-gray-400 transition-colors duration-150 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Form body --}}
                {{-- BRD: CRM-LC-011 — modal requires JS (Alpine) to open; JS fetch to API is the only submit path --}}
                <form id="create-lead-modal-form"
                      @submit.prevent="submitModal()"
                      class="px-6 py-5 space-y-5"
                      novalidate>

                    {{-- Name row --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="ml_first_name">First Name <span class="text-red-500">*</span></label>
                            <input id="ml_first_name" name="first_name" type="text" x-model="form.first_name"
                                   :class="{'border-red-500 focus:border-red-500 focus:ring-red-500': errors.first_name}"
                                   class="input-field" placeholder="Arjun" autocomplete="given-name">
                            <p x-show="errors.first_name" x-text="errors.first_name" role="alert" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="label" for="ml_last_name">Last Name <span class="text-red-500">*</span></label>
                            <input id="ml_last_name" name="last_name" type="text" x-model="form.last_name"
                                   :class="{'border-red-500 focus:border-red-500 focus:ring-red-500': errors.last_name}"
                                   class="input-field" placeholder="Sharma" autocomplete="family-name">
                            <p x-show="errors.last_name" x-text="errors.last_name" role="alert" class="mt-1 text-xs text-red-600"></p>
                        </div>
                    </div>

                    {{-- Contact --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="ml_mobile">Mobile <span class="text-red-500">*</span></label>
                            <input id="ml_mobile" name="mobile" type="tel" x-model="form.mobile"
                                   :class="{'border-red-500 focus:border-red-500 focus:ring-red-500': errors.mobile}"
                                   class="input-field" placeholder="9876543210" pattern="[6-9][0-9]{9}" maxlength="10">
                            <p class="mt-1 text-xs text-gray-400">10-digit Indian mobile number</p>
                            <p x-show="errors.mobile" x-text="errors.mobile" role="alert" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="label" for="ml_email">Email</label>
                            <input id="ml_email" name="email" type="email" x-model="form.email"
                                   :class="{'border-red-500 focus:border-red-500 focus:ring-red-500': errors.email}"
                                   class="input-field" placeholder="arjun@example.com" autocomplete="email">
                            <p x-show="errors.email" x-text="errors.email" role="alert" class="mt-1 text-xs text-red-600"></p>
                        </div>
                    </div>

                    {{-- Source — BRD: CRM-LC-014 mandatory source field --}}
                    <div>
                        <label class="label" for="ml_source">Lead Source <span class="text-red-500">*</span></label>
                        <select id="ml_source" name="source" x-model="form.source"
                                :class="{'border-red-500 focus:border-red-500 focus:ring-red-500': errors.source}"
                                class="input-field">
                            <option value="">— Select Source —</option>
                            @foreach($sourceOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.source" x-text="errors.source" role="alert" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    {{-- UTM params (shown only for digital sources) --}}
                    <div x-show="['google_ads','facebook','website_organic'].includes(form.source)"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-4 space-y-3">
                        <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">UTM Parameters</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label text-xs" for="ml_utm_source">utm_source</label>
                                <input id="ml_utm_source" type="text" x-model="form.source_utm_params.utm_source" class="input-field text-sm" placeholder="google">
                            </div>
                            <div>
                                <label class="label text-xs" for="ml_utm_medium">utm_medium</label>
                                <input id="ml_utm_medium" type="text" x-model="form.source_utm_params.utm_medium" class="input-field text-sm" placeholder="cpc">
                            </div>
                            <div class="col-span-2">
                                <label class="label text-xs" for="ml_utm_campaign">utm_campaign</label>
                                <input id="ml_utm_campaign" type="text" x-model="form.source_utm_params.utm_campaign" class="input-field text-sm" placeholder="mba-2026">
                            </div>
                        </div>
                    </div>

                    {{-- Location --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="ml_city">City</label>
                            <input id="ml_city" name="city" type="text" x-model="form.city" class="input-field" placeholder="Bengaluru">
                        </div>
                        <div>
                            <label class="label" for="ml_state">State</label>
                            <input id="ml_state" name="state" type="text" x-model="form.state" class="input-field" placeholder="Karnataka">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="label" for="ml_notes">Notes</label>
                        <textarea id="ml_notes" name="notes" x-model="form.notes" rows="3"
                                  class="input-field resize-none" placeholder="Any relevant details about this lead…" maxlength="1000"></textarea>
                    </div>

                    {{-- DPDP Consent — BRD: CRM-CR-001 consent at capture --}}
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" x-model="form.consent_given" name="consent_given"
                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">
                                <strong class="text-gray-900">I confirm that</strong> the prospective student has given explicit consent
                                to be contacted by our institution for admission-related communication, in compliance with
                                the <strong>DPDP Act 2023</strong>. <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <input type="hidden" name="consent_form_version" value="v1.0-2026-04">
                        <p x-show="errors.consent_given" x-text="errors.consent_given" role="alert" class="mt-2 text-xs text-red-600"></p>
                    </div>

                    {{-- Global error banner --}}
                    <div x-show="globalError" x-text="globalError" role="alert"
                         class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                </form>

                {{-- Modal footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <button
                        type="button"
                        class="btn-secondary"
                        @click="closeModal()"
                        :disabled="submitting"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        form="create-lead-modal-form"
                        class="btn-primary disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="submitting"
                    >
                        <span x-show="!submitting" class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Lead
                        </span>
                        <span x-show="submitting" class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 0v12z"></path>
                            </svg>
                            Saving…
                        </span>
                    </button>
                </div>
            </div>
        </div>

        @endcan
    </div>

    @push('scripts')
    <script>
    function leadModal() {
        const emptyForm = () => ({
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
        });

        return {
            open: false,
            toastVisible: false,
            submitting: false,
            globalError: '',
            errors: {},
            form: emptyForm(),

            closeModal() {
                this.open        = false;
                this.submitting  = false;
                this.globalError = '';
                this.errors      = {};
                this.form        = emptyForm();
            },

            async submitModal() {
                this.submitting  = true;
                this.errors      = {};
                this.globalError = '';

                const payload = { ...this.form };
                if (!['google_ads', 'facebook', 'website_organic'].includes(this.form.source)) {
                    delete payload.source_utm_params;
                }

                try {
                    const res = await fetch('{{ route('crm.leads.store') }}', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });

                    const json = await res.json();

                    if (res.status === 201) {
                        this.closeModal();
                        Livewire.dispatch('lead-created');
                        this.toastVisible = true;
                        setTimeout(() => { this.toastVisible = false; }, 4500);
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

