{{-- BRD: CRM-CC-002 — Send Email modal (Livewire) — triggered from lead detail sidebar --}}
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
        aria-labelledby="send-email-modal-title"
        x-on:keydown.escape.window="$wire.closeModal()"
    >
        <div class="relative my-auto w-full max-w-2xl rounded-xl bg-white shadow-2xl" wire:click.stop>

            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 id="send-email-modal-title" class="text-lg font-semibold text-gray-900">Send Email</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Select a template or compose a custom message.</p>
                </div>
                <button
                    type="button"
                    wire:click="closeModal"
                    aria-label="Close send email modal"
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

                {{-- Template selector --}}
                <div>
                    <label for="email-template" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Template
                    </label>
                    <select
                        id="email-template"
                        wire:model.live="templateId"
                        class="input-field w-full"
                    >
                        <option value="0">— Compose custom message —</option>
                        @foreach($this->emailTemplates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}{{ $tpl->subject ? ' · ' . $tpl->subject : '' }}</option>
                        @endforeach
                    </select>
                    @if($this->emailTemplates->isEmpty())
                        <p class="mt-1 text-xs text-amber-600">No active email templates found. <a href="{{ route('crm.communication.templates.create') }}" class="underline hover:text-amber-800">Create one</a>.</p>
                    @endif
                </div>

                {{-- Custom subject (only when no template selected) --}}
                @if((int)$templateId === 0)
                    <div>
                        <label for="email-subject" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Subject
                        </label>
                        <input
                            id="email-subject"
                            type="text"
                            wire:model.lazy="customSubject"
                            placeholder="Enter email subject..."
                            class="input-field w-full"
                            maxlength="255"
                        >
                    </div>

                    <div>
                        <label for="email-body" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Message
                            <span class="ml-1 text-xs font-normal text-gray-400">(HTML supported)</span>
                        </label>
                        <textarea
                            id="email-body"
                            wire:model.lazy="customBodyHtml"
                            rows="6"
                            placeholder="Compose your message here..."
                            class="input-field w-full resize-y font-mono text-sm"
                            maxlength="50000"
                        ></textarea>
                    </div>
                @endif

                {{-- Preview pane (shown when a template is selected) --}}
                @if((int)$templateId > 0 && $preview)
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-gray-700">Preview</p>
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm leading-relaxed text-gray-700">
                            {!! $preview !!}
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Merge tags have been pre-filled with this lead's data.</p>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Send Email
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
