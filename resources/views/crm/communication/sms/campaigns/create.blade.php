<x-layouts.crm title="New SMS Campaign">
    <div class="max-w-3xl space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('crm.communication.sms.campaigns.index') }}"
               class="flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500"
               aria-label="Back to SMS campaigns">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">New SMS Campaign</h1>
                <p class="mt-0.5 text-sm text-gray-500">Send a bulk SMS to your leads using a TRAI-approved DLT template.</p>
            </div>
        </div>

        {{-- Validation Errors --}}
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

        <form method="POST" action="{{ route('crm.communication.sms.campaigns.store') }}">
            @csrf

            {{-- Card: Campaign Details --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Campaign Details</h2>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Campaign Name --}}
                    <div>
                        <label for="sms_name" class="label">
                            Campaign Name <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="sms_name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            maxlength="120"
                            placeholder="e.g. May 2026 — Open Day SMS Blast"
                            @class(['input-field mt-1', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('name')])
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Card: DLT Template & Gateway --}}
            <div class="mt-4 bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">DLT Template &amp; Gateway</h2>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- DLT Template --}}
                    <div>
                        <label for="sms_dlt_template" class="label">
                            DLT Template <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <div class="relative mt-1">
                            <select
                                id="sms_dlt_template"
                                name="dlt_template_id"
                                required
                                @class(['input-field appearance-none pr-8 cursor-pointer', 'border-red-500' => $errors->has('dlt_template_id')])
                            >
                                <option value="">— Select approved DLT template —</option>
                                @foreach ($dltTemplates as $tpl)
                                    <option value="{{ $tpl->id }}" @selected(old('dlt_template_id') == $tpl->id)>
                                        {{ $tpl->template_name }}
                                        @if ($tpl->dlt_template_id)
                                            ({{ $tpl->dlt_template_id }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        @error('dlt_template_id')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        @if ($dltTemplates->isEmpty())
                            <p class="mt-1.5 text-xs text-amber-600">
                                No approved DLT templates found.
                                <a href="{{ route('crm.communication.sms.dlt.templates.create') }}" class="underline hover:text-amber-700">Register one →</a>
                            </p>
                        @else
                            <p class="mt-1.5 text-xs text-gray-400">Only TRAI-approved templates are listed.</p>
                        @endif
                    </div>

                    {{-- SMS Gateway --}}
                    <div>
                        <label for="sms_gateway" class="label">
                            SMS Gateway <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <div class="relative mt-1">
                            <select
                                id="sms_gateway"
                                name="gateway"
                                required
                                @class(['input-field appearance-none pr-8 cursor-pointer', 'border-red-500' => $errors->has('gateway')])
                            >
                                @foreach (\App\Enums\CRM\SmsGateway::cases() as $gw)
                                    <option value="{{ $gw->value }}" @selected(old('gateway') === $gw->value)>
                                        {{ $gw->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        @error('gateway')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Card: Scheduling --}}
            <div class="mt-4 bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Scheduling</h2>
                </div>

                <div class="px-6 py-5">
                    <div class="flex items-start gap-3 mb-4 px-3 py-2.5 bg-blue-50 border border-blue-100 rounded-lg">
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-blue-700">Leave blank to save as a <strong>Draft</strong>. You can launch it manually from the campaign detail page.</p>
                    </div>

                    <div class="sm:w-72">
                        <label for="sms_scheduled_at" class="label">Schedule Send Time</label>
                        <input
                            type="datetime-local"
                            id="sms_scheduled_at"
                            name="scheduled_at"
                            value="{{ old('scheduled_at') }}"
                            @class(['input-field mt-1', 'border-red-500' => $errors->has('scheduled_at')])
                        />
                        @error('scheduled_at')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('crm.communication.sms.campaigns.index') }}" class="btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn-primary gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Create Campaign
                </button>
            </div>

        </form>
    </div>
</x-layouts.crm>

