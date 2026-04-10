<x-layouts.crm title="New Communication Template">
    <div class="max-w-3xl space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('crm.communication.templates.index') }}"
               class="flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500"
               aria-label="Back to templates">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">New Communication Template</h1>
                <p class="mt-0.5 text-sm text-gray-500">Create a reusable template for email, SMS, or WhatsApp campaigns.</p>
            </div>
        </div>

        {{-- Merge Tags Info --}}
        <div class="flex items-start gap-3 px-4 py-3 bg-indigo-50 border border-indigo-100 rounded-lg">
            <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-indigo-700">
                Available merge tags:
                <code class="mx-1 px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded text-xs font-mono">@{{name}}</code>
                <code class="mx-1 px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded text-xs font-mono">@{{programme}}</code>
                <code class="mx-1 px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded text-xs font-mono">@{{counsellor}}</code>
                <code class="mx-1 px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded text-xs font-mono">@{{institution}}</code>
                <code class="mx-1 px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded text-xs font-mono">@{{unsubscribe_link}}</code>
            </p>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg" role="alert">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-red-800">Please fix the following errors:</p>
                    <ul class="mt-1 list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li class="text-sm text-red-700">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Form --}}
        <form
            method="POST"
            action="{{ route('crm.communication.templates.store') }}"
            x-data="{ channel: '{{ old('channel', 'EMAIL') }}', type: '{{ old('type', 'TRANSACTIONAL') }}' }"
        >
            @csrf

            {{-- Card: Basic Info --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Template Details</h2>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Template Name --}}
                    <div>
                        <label for="tpl_name" class="label">
                            Template Name <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="tpl_name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            maxlength="120"
                            placeholder="e.g. Welcome Email – Enquiry Received"
                            @class(['input-field mt-1', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('name')])
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Channel + Type --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                        <div>
                            <label for="tpl_channel" class="label">
                                Channel <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <div class="relative mt-1">
                                <select
                                    id="tpl_channel"
                                    name="channel"
                                    x-model="channel"
                                    @class(['input-field appearance-none pr-8 cursor-pointer', 'border-red-500' => $errors->has('channel')])
                                >
                                    @foreach (\App\Enums\CRM\CommunicationChannel::cases() as $c)
                                        <option value="{{ $c->value }}" @selected(old('channel', 'EMAIL') === $c->value)>
                                            {{ $c->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                            @error('channel')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tpl_type" class="label">
                                Template Type <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <div class="relative mt-1">
                                <select
                                    id="tpl_type"
                                    name="type"
                                    x-model="type"
                                    @class(['input-field appearance-none pr-8 cursor-pointer', 'border-red-500' => $errors->has('type')])
                                >
                                    @foreach (\App\Enums\CRM\TemplateType::cases() as $t)
                                        <option value="{{ $t->value }}" @selected(old('type', 'TRANSACTIONAL') === $t->value)>
                                            {{ $t->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- Language --}}
                    <div class="sm:w-40">
                        <label for="tpl_language" class="label">Language <span class="text-xs text-gray-400 font-normal">(IETF code)</span></label>
                        <input
                            type="text"
                            id="tpl_language"
                            name="language"
                            value="{{ old('language', 'en') }}"
                            maxlength="10"
                            placeholder="en"
                            @class(['input-field mt-1', 'border-red-500' => $errors->has('language')])
                        />
                        @error('language')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Card: Content --}}
            <div class="mt-4 bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                <div class="px-6 py-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Content</h2>
                    <span class="text-xs text-gray-400">Required fields marked <span class="text-red-500">*</span></span>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Subject (email only) --}}
                    <div x-show="channel === 'EMAIL'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <label for="tpl_subject" class="label">
                            Subject Line
                            <span class="text-red-500" x-show="channel === 'EMAIL'" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="tpl_subject"
                            name="subject"
                            value="{{ old('subject') }}"
                            maxlength="998"
                            placeholder="e.g. Welcome to @{{institution}}, @{{name}}!"
                            @class(['input-field mt-1', 'border-red-500' => $errors->has('subject')])
                        />
                        @error('subject')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Body --}}
                    <div>
                        <label for="tpl_body" class="label">
                            Body <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="tpl_body"
                            name="body_text"
                            rows="10"
                            required
                            placeholder="Write your template content here. Use merge tags like @{{name}} to personalise."
                            @class(['input-field mt-1 font-mono text-sm resize-y', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('body_text')])
                        >{{ old('body_text') }}</textarea>
                        @error('body_text')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-gray-400">
                            For Marketing type, include <code class="px-1 bg-gray-100 rounded font-mono">@{{unsubscribe_link}}</code> — required by DPDP Act 2023.
                        </p>
                    </div>

                    {{-- DPDP marketing warning --}}
                    <div
                        x-show="type === 'MARKETING'"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="flex items-start gap-2 px-3 py-2.5 bg-amber-50 border border-amber-200 rounded-lg"
                    >
                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-xs text-amber-700">
                            <strong>DPDP Act 2023:</strong> Marketing templates must include an <code class="font-mono">@{{unsubscribe_link}}</code> tag. Unsubscribes take effect within 24 hours.
                        </p>
                    </div>

                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('crm.communication.templates.index') }}"
                   class="btn-secondary">
                    Cancel
                </a>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="btn-primary gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Save Template
                </button>
            </div>

        </form>

    </div>
</x-layouts.crm>

