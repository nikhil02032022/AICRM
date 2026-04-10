{{-- BRD: CRM-CC-006 — Send SMS modal (Livewire) — triggered from lead detail sidebar --}}
<div>
@if($showModal)

    {{-- Backdrop --}}
    <div
        class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm"
        wire:click="closeModal"
        aria-hidden="true"
    ></div>

    {{-- Dialog --}}
    <div
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="send-sms-modal-title"
        x-on:keydown.escape.window="$wire.closeModal()"
    >
        <div class="relative my-auto w-full max-w-lg rounded-xl bg-white shadow-2xl" wire:click.stop>

            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    {{-- SMS icon badge --}}
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-4.5 w-4.5 h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 id="send-sms-modal-title" class="text-lg font-semibold text-gray-900">Send SMS</h2>
                        <p class="mt-0.5 text-sm text-gray-500">Select an approved DLT template to send.</p>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="closeModal"
                    aria-label="Close send SMS modal"
                    class="ml-4 flex-shrink-0 rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="space-y-5 px-6 py-5">

                {{-- Success alert --}}
                @if($successMessage)
                    <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3" role="alert">
                        <svg class="h-4 w-4 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm font-medium text-green-800">{{ $successMessage }}</p>
                    </div>
                @endif

                {{-- Error alert --}}
                @if($errorMessage)
                    <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3" role="alert">
                        <svg class="h-4 w-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-red-800">{{ $errorMessage }}</p>
                    </div>
                @endif

                {{-- DPDP notice: only approved DLT templates allowed --}}
                <div class="flex items-start gap-2 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2.5">
                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-amber-700">
                        <strong>TRAI compliance:</strong> Only APPROVED DLT templates are available. The system substitutes lead data for <code class="font-mono text-amber-800">{#var#}</code> variables automatically.
                    </p>
                </div>

                {{-- DLT Template selector --}}
                <div>
                    <label for="sms-template" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        DLT Template <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <div class="relative">
                        <select
                            id="sms-template"
                            wire:model.live="templateId"
                            class="input-field w-full appearance-none pr-8 cursor-pointer"
                        >
                            <option value="0">— Select an approved template —</option>
                            @foreach($this->smsTemplates as $tpl)
                                <option value="{{ $tpl->id }}">
                                    {{ $tpl->template_name }}
                                    @if($tpl->sender_id)
                                        · {{ $tpl->sender_id }}
                                    @endif
                                    · {{ $tpl->gateway->value }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                    @if($this->smsTemplates->isEmpty())
                        <p class="mt-1 text-xs text-amber-600">
                            No approved DLT templates found.
                            <a href="{{ route('crm.communication.sms.dlt.templates.create') }}" class="underline hover:text-amber-800">Register one</a>.
                        </p>
                    @endif
                </div>

                {{-- Template preview pane --}}
                @if((int)$templateId > 0 && $preview)
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-gray-700">Message Preview</p>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-relaxed text-gray-700">
                            {!! $preview !!}
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Lead data has been substituted for template variables.</p>
                    </div>
                @endif

                @if((int)$templateId > 0 && !$preview)
                    <div wire:loading wire:target="templateId" class="flex items-center gap-2 text-xs text-gray-500">
                        <svg class="h-3 w-3 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Loading preview…
                    </div>
                @endif

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button
                    type="button"
                    wire:click="closeModal"
                    class="btn-secondary px-4 py-2 text-sm"
                >
                    Cancel
                </button>

                @if(!$successMessage)
                    <button
                        type="button"
                        wire:click="send"
                        wire:loading.attr="disabled"
                        wire:target="send"
                        :disabled="{{ json_encode($isSubmitting) }}"
                        class="btn-primary inline-flex items-center gap-2 px-5 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="send" class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                            </svg>
                            Send SMS
                        </span>
                        <span wire:loading wire:target="send" class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            Sending…
                        </span>
                    </button>
                @endif
            </div>

        </div>
    </div>

@endif
</div>
