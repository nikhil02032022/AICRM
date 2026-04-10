<x-layouts.crm title="Register DLT Template">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('crm.communication.sms.dlt.templates.index') }}"
                   class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors duration-150">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                    Back to DLT Templates
                </a>
                <h1 class="text-2xl font-bold leading-tight text-gray-900">Register DLT SMS Template</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Register your TRAI-approved template.
                    Use <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700">{#var#}</code>
                    for variable fields as required by TRAI DLT portal.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- ── Main form (2/3 width) ── --}}
            <div class="lg:col-span-2">

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
                        <div class="flex gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-red-800">Please fix the following errors:</p>
                                <ul class="mt-1 list-inside list-disc text-sm text-red-700 space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('crm.communication.sms.dlt.templates.store') }}"
                    x-data="{ submitting: false, bodyLen: {{ strlen(old('template_body', '')) }} }"
                    @submit="submitting = true"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    @csrf

                    {{-- Section: Identity --}}
                    <div class="border-b border-gray-100 px-6 py-5">
                        <h2 class="text-sm font-semibold text-gray-900">Template Identity</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Internal name and your TRAI-issued template identifier.</p>
                    </div>
                    <div class="space-y-5 px-6 py-5">

                        {{-- Template Name --}}
                        <div>
                            <label for="template_name" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Template Name <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                id="template_name"
                                name="template_name"
                                value="{{ old('template_name') }}"
                                required
                                maxlength="120"
                                placeholder="e.g. Application Received Notification"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm transition-colors duration-150 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('template_name') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            >
                            <p class="mt-1 text-xs text-gray-400">Internal label only — not visible to recipients. Max 120 chars.</p>
                            @error('template_name')
                                <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- DLT Template ID --}}
                        <div>
                            <label for="dlt_template_id" class="mb-1.5 block text-sm font-medium text-gray-700">
                                DLT Template ID
                                <span class="ml-1.5 rounded bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700">Optional</span>
                            </label>
                            <input
                                type="text"
                                id="dlt_template_id"
                                name="dlt_template_id"
                                value="{{ old('dlt_template_id') }}"
                                maxlength="50"
                                placeholder="Add after receiving TRAI portal approval"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono text-gray-900 shadow-sm transition-colors duration-150 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('dlt_template_id') border-red-400 @enderror"
                            >
                            <p class="mt-1 text-xs text-gray-400">Your gateway's DLT Template ID issued after TRAI approval. You can add this later.</p>
                            @error('dlt_template_id')
                                <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Section: Configuration --}}
                    <div class="border-y border-gray-100 bg-gray-50/50 px-6 py-5">
                        <h2 class="text-sm font-semibold text-gray-900">SMS Configuration</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Gateway, message type, and registered sender ID.</p>
                    </div>
                    <div class="space-y-5 px-6 py-5">

                        {{-- Gateway --}}
                        <div>
                            <label for="gateway" class="mb-1.5 block text-sm font-medium text-gray-700">
                                SMS Gateway <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <select
                                id="gateway"
                                name="gateway"
                                required
                                class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition-colors duration-150 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('gateway') border-red-400 @enderror"
                            >
                                <option value="">Select gateway…</option>
                                @foreach (\App\Enums\CRM\SmsGateway::cases() as $gw)
                                    <option value="{{ $gw->value }}" @selected(old('gateway') === $gw->value)>
                                        {{ $gw->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400">The SMS gateway this template will be dispatched through.</p>
                            @error('gateway')
                                <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                            {{-- Message Type --}}
                            <div>
                                <label for="message_type" class="mb-1.5 block text-sm font-medium text-gray-700">
                                    Message Type <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <select
                                    id="message_type"
                                    name="message_type"
                                    required
                                    class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition-colors duration-150 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('message_type') border-red-400 @enderror"
                                >
                                    @foreach (\App\Enums\CRM\DltMessageType::cases() as $t)
                                        <option value="{{ $t->value }}" @selected(old('message_type') === $t->value)>
                                            {{ $t->value }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('message_type')
                                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Sender ID --}}
                            <div>
                                <label for="sender_id" class="mb-1.5 block text-sm font-medium text-gray-700">
                                    Sender ID <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="sender_id"
                                    name="sender_id"
                                    value="{{ old('sender_id') }}"
                                    required
                                    maxlength="6"
                                    minlength="6"
                                    placeholder="MEETCS"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono font-semibold uppercase tracking-widest text-gray-900 shadow-sm transition-colors duration-150 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('sender_id') border-red-400 @enderror"
                                    oninput="this.value = this.value.toUpperCase()"
                                >
                                <p class="mt-1 text-xs text-gray-400">Exactly 6 characters — your TRAI-registered alphanumeric sender ID.</p>
                                @error('sender_id')
                                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section: Template Body --}}
                    <div class="border-y border-gray-100 bg-gray-50/50 px-6 py-5">
                        <h2 class="text-sm font-semibold text-gray-900">Template Body</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Exact message text as registered (or to be registered) at TRAI portal.</p>
                    </div>
                    <div class="px-6 py-5">
                        <label for="template_body" class="mb-1.5 block text-sm font-medium text-gray-700">
                            Message Body <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="template_body"
                            name="template_body"
                            rows="6"
                            required
                            maxlength="2000"
                            placeholder="Dear {#var#}, your application for {#var#} has been received. Ref: {#var#} - MEETCS"
                            x-on:input="bodyLen = $el.value.length"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono text-sm text-gray-900 shadow-sm transition-colors duration-150 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 @error('template_body') border-red-400 @enderror"
                        >{{ old('template_body') }}</textarea>
                        <div class="mt-1.5 flex items-start justify-between gap-4">
                            <p class="text-xs text-gray-400">
                                Use <code class="rounded bg-gray-100 px-1 font-mono text-gray-600">{#var#}</code> for each variable placeholder exactly as submitted to TRAI.
                            </p>
                            <p class="shrink-0 text-xs tabular-nums"
                               :class="bodyLen > 1800 ? 'text-red-500 font-medium' : 'text-gray-400'">
                                <span x-text="bodyLen"></span> / 2000
                            </p>
                        </div>
                        @error('template_body')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Footer actions --}}
                    <div class="flex items-center justify-between gap-4 border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                        <a href="{{ route('crm.communication.sms.dlt.templates.index') }}"
                           class="btn-secondary text-sm">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="btn-primary gap-2 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg x-show="!submitting" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"/>
                            </svg>
                            <span x-text="submitting ? 'Saving…' : 'Save Template'"></span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- ── Info sidebar (1/3 width) ── --}}
            <div class="space-y-5">

                {{-- DLT Process guide --}}
                <div class="overflow-hidden rounded-xl border border-indigo-100 bg-indigo-50">
                    <div class="flex items-center gap-2.5 border-b border-indigo-100 bg-indigo-100/60 px-4 py-3">
                        <svg class="h-4 w-4 shrink-0 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                        </svg>
                        <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wider">DLT Registration Process</span>
                    </div>
                    <ol class="space-y-3 p-4">
                        @foreach ([
                            ['step' => '1', 'title' => 'Save as Draft', 'desc' => 'Register the template here first with your message body.'],
                            ['step' => '2', 'title' => 'Submit to TRAI Portal', 'desc' => 'Log into your gateway\'s DLT portal and submit the exact same template text.'],
                            ['step' => '3', 'title' => 'Receive DLT Template ID', 'desc' => 'Once approved, your gateway provides a DLT Template ID.'],
                            ['step' => '4', 'title' => 'Mark as Approved', 'desc' => 'Return here, edit the template, enter the DLT Template ID, and mark as Approved.'],
                        ] as $item)
                        <li class="flex gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-200 text-xs font-bold text-indigo-700">{{ $item['step'] }}</span>
                            <div>
                                <p class="text-xs font-semibold text-indigo-900">{{ $item['title'] }}</p>
                                <p class="mt-0.5 text-xs text-indigo-700 leading-relaxed">{{ $item['desc'] }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Variable syntax reminder --}}
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                        </svg>
                        <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Variable Syntax</span>
                    </div>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        TRAI requires variables to be written as <code class="rounded bg-amber-100 px-1 font-mono">{#var#}</code>.
                        Each <code class="rounded bg-amber-100 px-1 font-mono">{#var#}</code> in your template counts as one variable slot.
                    </p>
                    <div class="mt-3 rounded-lg bg-amber-100/70 p-3 font-mono text-xs text-amber-900 leading-relaxed">
                        Dear {#var#}, your application for {#var#} has been received. Ref: {#var#}
                    </div>
                </div>

                {{-- Compliance note --}}
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                        </svg>
                        <span class="text-xs font-semibold text-gray-600">Compliance Note</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Only <span class="font-medium text-gray-700">APPROVED</span> templates can be used to send SMS.
                        DRAFT and PENDING templates are blocked at dispatch — protecting your institution from TRAI violations.
                    </p>
                </div>

            </div>
        </div>

    </div>
</x-layouts.crm>
