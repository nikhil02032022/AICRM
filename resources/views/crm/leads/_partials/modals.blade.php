        {{-- ===== Edit Lead Modal ===== --}}
        @can('crm.leads.edit', $lead)

    {{-- Backdrop --}}
    <div x-show="editOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm"
         @click="closeEdit()"
         aria-hidden="true"
         style="display:none"
    ></div>

    {{-- Dialog --}}
    <div id="edit-lead-modal"
         x-show="editOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6"
         role="dialog" aria-modal="true" aria-labelledby="edit-modal-title"
         @keydown.escape.window="closeEdit()"
         style="display:none"
    >
        <div class="relative my-auto w-full max-w-2xl rounded-xl bg-white shadow-2xl" @click.stop>

            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 id="edit-modal-title" class="text-lg font-semibold text-gray-900">Edit Lead</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Update lead details — changes are saved immediately.</p>
                </div>
                <button type="button" @click="closeEdit()" aria-label="Close"
                        class="ml-4 flex-shrink-0 rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form id="edit-lead-form" @submit.prevent="submitEdit()" class="space-y-5 px-6 py-5" novalidate>

                {{-- Name --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="el_first_name">First Name <span class="text-red-500">*</span></label>
                        <input id="el_first_name" type="text" x-model="editForm.first_name"
                               :class="{'border-red-500': editErrors.first_name}"
                               class="input-field" autocomplete="given-name">
                        <p x-show="editErrors.first_name" x-text="editErrors.first_name" role="alert" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="label" for="el_last_name">Last Name <span class="text-red-500">*</span></label>
                        <input id="el_last_name" type="text" x-model="editForm.last_name"
                               :class="{'border-red-500': editErrors.last_name}"
                               class="input-field" autocomplete="family-name">
                        <p x-show="editErrors.last_name" x-text="editErrors.last_name" role="alert" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>

                {{-- Email --}}
                <div>
                    <label class="label" for="el_email">Email</label>
                    <input id="el_email" type="email" x-model="editForm.email"
                           :class="{'border-red-500': editErrors.email}"
                           class="input-field" autocomplete="email">
                    <p x-show="editErrors.email" x-text="editErrors.email" role="alert" class="mt-1 text-xs text-red-600"></p>
                </div>

                {{-- Source + Status --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="el_source">Lead Source</label>
                        <select id="el_source" x-model="editForm.source" class="input-field">
                            <option value="">— Select Source —</option>
                            @foreach($sourceOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="el_status">Status</label>
                        <select id="el_status" x-model="editForm.status" class="input-field">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- BRD: CRM-EC-013 — Lost reason required when status is LOST --}}
                <div x-show="editForm.status === 'lost'" x-transition>
                    <label class="label" for="el_lost_reason">
                        Reason for Loss <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select id="el_lost_reason" x-model="editForm.lost_reason"
                            :required="editForm.status === 'lost'"
                            :class="{'border-red-500': editErrors.lost_reason}"
                            class="input-field">
                        <option value="">— Select a reason —</option>
                        @foreach(\App\Enums\CRM\LostReason::optionsForSelect() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p x-show="editErrors.lost_reason" x-text="editErrors.lost_reason" role="alert" class="mt-1 text-xs text-red-600"></p>
                </div>

                {{-- City + State --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="el_city">City</label>
                        <input id="el_city" type="text" x-model="editForm.city" class="input-field">
                    </div>
                    <div>
                        <label class="label" for="el_state">State</label>
                        <input id="el_state" type="text" x-model="editForm.state" class="input-field">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="label" for="el_notes">Notes</label>
                    <textarea id="el_notes" x-model="editForm.notes" rows="3"
                              class="input-field resize-none" maxlength="1000"></textarea>
                </div>

                {{-- Global error --}}
                <div x-show="editGlobalError" x-text="editGlobalError" role="alert"
                     class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            </form>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button type="button" @click="closeEdit()" :disabled="editSubmitting" class="btn-secondary">Cancel</button>
                <button type="submit" form="edit-lead-form" :disabled="editSubmitting"
                        class="btn-primary disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="!editSubmitting" class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </span>
                    <span x-show="editSubmitting" class="flex items-center gap-2">
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

    {{-- ===== Delete Confirmation Modal ===== --}}
    @can('crm.leads.delete', $lead)

    <div x-show="deleteOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm"
         @click="closeDelete()"
         aria-hidden="true"
         style="display:none"
    ></div>

    <div x-show="deleteOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="delete-modal-title"
         @keydown.escape.window="closeDelete()"
         style="display:none"
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="delete-modal-title" class="text-base font-semibold text-gray-900">Archive Lead</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Are you sure you want to archive <strong>{{ $lead->fullName() }}</strong>?
                        The lead will be soft-deleted and can be restored by an admin.
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closeDelete()" :disabled="deleteSubmitting" class="btn-secondary">Cancel</button>
                <button type="button" @click="submitDelete()" :disabled="deleteSubmitting"
                        class="btn-primary !bg-red-600 !border-red-600 hover:!bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="!deleteSubmitting">Archive Lead</span>
                    <span x-show="deleteSubmitting" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 0v12z"></path>
                        </svg>
                        Archiving…
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endcan

