{{-- BRD: CRM-LC-019 — Lead merge confirmation modal --}}
{{-- Only rendered for users with crm.leads.merge permission --}}
@can('crm.leads.merge', $lead)
@if($lead->is_duplicate_suspected && $lead->duplicate_of_uuid)

{{-- Backdrop --}}
<div x-show="mergeOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm"
     @click="mergeOpen = false"
     aria-hidden="true"
     style="display:none"></div>

{{-- Dialog --}}
<div id="merge-lead-modal"
     x-show="mergeOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-4 scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 translate-y-4 scale-95"
     class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6"
     role="dialog" aria-modal="true" aria-labelledby="merge-modal-title"
     @keydown.escape.window="mergeOpen = false"
     style="display:none">

    <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-200 my-8">

        {{-- Header --}}
        <div class="flex items-start gap-4 mb-5">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-rose-100">
                <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 id="merge-modal-title" class="text-base font-semibold text-gray-900">Merge Duplicate Leads</h3>
                <p class="mt-1 text-sm text-gray-500">
                    This action is <strong>irreversible</strong>. The secondary lead will be permanently archived
                    and all its data transferred to the primary lead.
                </p>
            </div>
            <button type="button" @click="mergeOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Lead comparison --}}
        <div class="mb-5 grid grid-cols-2 gap-3">
            {{-- Primary (this lead — survives) --}}
            <div class="rounded-xl border-2 border-primary-200 bg-primary-50 p-4">
                <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-primary-600">Primary (Survives)</p>
                <p class="font-semibold text-gray-900">{{ $lead->fullName() }}</p>
                @can('crm.leads.view_pii', $lead)
                <p class="mt-0.5 font-mono text-xs text-gray-500">{{ $lead->mobile }}</p>
                @endcan
                <p class="mt-1 text-xs text-gray-500">Score: {{ $lead->lead_score }}/100</p>
                <p class="text-xs text-gray-500">Status: {{ $lead->status?->label() }}</p>
            </div>

            {{-- Secondary (will be merged in) --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-500">Secondary (Archived)</p>
                <p class="font-semibold text-gray-700">Suspected duplicate</p>
                <p class="mt-1 text-xs text-gray-400 font-mono break-all">{{ $lead->duplicate_of_uuid }}</p>
                <p class="mt-1 text-xs text-gray-500">Activities, sessions & contacts will be transferred to primary.</p>
            </div>
        </div>

        {{-- What transfers list --}}
        <div class="mb-5 rounded-lg border border-amber-100 bg-amber-50 p-3">
            <p class="text-xs font-semibold text-amber-800 mb-1.5">What will be transferred to the primary lead:</p>
            <ul class="space-y-0.5 text-xs text-amber-700">
                <li class="flex items-center gap-1.5"><svg class="h-3 w-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>All activity timeline entries</li>
                <li class="flex items-center gap-1.5"><svg class="h-3 w-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Counselling sessions</li>
                <li class="flex items-center gap-1.5"><svg class="h-3 w-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Programme interests</li>
                <li class="flex items-center gap-1.5"><svg class="h-3 w-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Score overrides &amp; communication logs</li>
                <li class="flex items-center gap-1.5"><svg class="h-3 w-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Null profile fields back-filled from secondary</li>
            </ul>
        </div>

        {{-- Confirmation input --}}
        <div class="mb-5">
            <label for="merge-confirm-input" class="block text-xs font-semibold text-gray-700 mb-1.5">
                Type <span class="font-mono bg-gray-100 px-1 py-0.5 rounded text-rose-700">MERGE</span> to confirm
            </label>
            <input id="merge-confirm-input"
                   type="text"
                   x-model="mergeConfirmText"
                   placeholder="Type MERGE to confirm"
                   autocomplete="off"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-rose-400 focus:outline-none focus:ring-1 focus:ring-rose-400"/>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <button type="button" @click="mergeOpen = false" :disabled="mergeSubmitting"
                    class="btn-secondary">Cancel</button>
            <button type="button"
                    @click="submitMerge()"
                    :disabled="mergeConfirmText !== 'MERGE' || mergeSubmitting"
                    class="inline-flex items-center gap-2 rounded-xl border border-rose-600 bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 disabled:cursor-not-allowed disabled:opacity-50 transition-colors">
                <span x-show="!mergeSubmitting">Merge Leads</span>
                <span x-show="mergeSubmitting" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 0v12z"></path>
                    </svg>
                    Merging…
                </span>
            </button>
        </div>

    </div>
</div>

@endif
@endcan
