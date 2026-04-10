<x-layouts.crm title="New Email Campaign">
    <div class="max-w-3xl space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('crm.communication.email.campaigns.index') }}"
               class="flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500"
               aria-label="Back to campaigns">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">New Email Campaign</h1>
                <p class="mt-0.5 text-sm text-gray-500">Compose and schedule a bulk email campaign to your leads.</p>
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

        <form method="POST" action="{{ route('crm.communication.email.campaigns.store') }}"
              x-data="{
                  senderDomains: @json($senderDomains->keyBy('id')),
                  fromName: @json(old('from_name', '')),
                  fromEmail: @json(old('from_email', '')),
                  onDomainChange(id) {
                      const d = this.senderDomains[id];
                      if (d) {
                          this.fromName  = d.default_from_name  ?? this.fromName;
                          this.fromEmail = d.default_from_email ?? this.fromEmail;
                      }
                  }
              }">
            @csrf

            {{-- Card: Campaign Details --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Campaign Details</h2>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Campaign Name --}}
                    <div>
                        <label for="campaign_name" class="label">
                            Campaign Name <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="campaign_name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            maxlength="120"
                            placeholder="e.g. May 2026 — Engineering Enquiry Follow-up"
                            @class(['input-field mt-1', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('name')])
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Subject Line --}}
                    <div>
                        <label for="campaign_subject" class="label">
                            Subject Line <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="campaign_subject"
                            name="subject"
                            value="{{ old('subject') }}"
                            required
                            maxlength="998"
                            placeholder="e.g. Your Application to @{{programme}} — Next Steps"
                            @class(['input-field mt-1', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('subject')])
                        />
                        @error('subject')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-gray-400">Merge tags like <code class="px-1 bg-gray-100 rounded font-mono">@{{name}}</code> are supported.</p>
                    </div>

                </div>
            </div>

            {{-- Card: Template & Sender --}}
            <div class="mt-4 bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Template &amp; Sender</h2>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Template --}}
                    <div>
                        <label for="campaign_template" class="label">
                            Email Template <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <div class="relative mt-1">
                            <select
                                id="campaign_template"
                                name="template_id"
                                required
                                @class(['input-field appearance-none pr-8 cursor-pointer', 'border-red-500' => $errors->has('template_id')])
                            >
                                <option value="">— Select a template —</option>
                                @foreach ($templates as $tpl)
                                    <option value="{{ $tpl->id }}" @selected(old('template_id') == $tpl->id)>
                                        {{ $tpl->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        @error('template_id')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        @if ($templates->isEmpty())
                            <p class="mt-1.5 text-xs text-amber-600">
                                No email templates found.
                                <a href="{{ route('crm.communication.templates.create') }}" class="underline hover:text-amber-700">Create one first →</a>
                            </p>
                        @endif
                    </div>

                    {{-- Sender Domain --}}
                    <div>
                        <label for="campaign_sender_domain" class="label">
                            Sender Domain
                            <span class="ml-1 text-xs text-gray-400 font-normal">(selecting one auto-fills From Name &amp; Email below)</span>
                        </label>
                        <div class="relative mt-1">
                            <select
                                id="campaign_sender_domain"
                                name="sender_domain_id"
                                @change="onDomainChange($event.target.value)"
                                @class(['input-field appearance-none pr-8 cursor-pointer', 'border-red-500' => $errors->has('sender_domain_id')])
                            >
                                <option value="">— Use default sender —</option>
                                @foreach ($senderDomains as $domain)
                                    <option value="{{ $domain->id }}" @selected(old('sender_domain_id') == $domain->id)>
                                        {{ $domain->domain }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        @error('sender_domain_id')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        @if ($senderDomains->isEmpty())
                            <p class="mt-1.5 text-xs text-amber-600">
                                No verified sender domains.
                                <a href="{{ route('crm.settings.sender-domains.index') }}" class="underline hover:text-amber-700">Set one up →</a>
                            </p>
                        @endif
                    </div>

                    {{-- From Name & From Email (side-by-side) --}}
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="from_name" class="label">
                                From Name <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                id="from_name"
                                name="from_name"
                                x-model="fromName"
                                required
                                maxlength="100"
                                placeholder="e.g. GIM Admissions Team"
                                @class(['input-field mt-1', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('from_name')])
                            />
                            @error('from_name')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="from_email" class="label">
                                From Email <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="email"
                                id="from_email"
                                name="from_email"
                                x-model="fromEmail"
                                required
                                maxlength="255"
                                placeholder="e.g. admissions@gim.ac.in"
                                @class(['input-field mt-1', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('from_email')])
                            />
                            @error('from_email')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">Must match a verified sender domain.</p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Card: Recipient Segment --}}
            {{-- BRD: CRM-CC-003 — Segment leads by status, source, temperature, date range --}}
            <div class="mt-4 bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                <div class="px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Recipient Segment</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Leave all filters blank to include all leads with a valid email address.</p>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-6">

                    {{-- Lead Status --}}
                    <div>
                        <p class="mb-2.5 text-sm font-medium text-gray-700">Lead Status</p>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($statuses as $status)
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        name="recipient_filter[statuses][]"
                                        value="{{ $status->value }}"
                                        @checked(in_array($status->value, old('recipient_filter.statuses', [])))
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span class="text-sm text-gray-700">{{ $status->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Lead Temperature --}}
                    <div class="border-t border-gray-100 pt-5">
                        <p class="mb-2.5 text-sm font-medium text-gray-700">Lead Temperature</p>
                        <div class="flex flex-wrap gap-x-8 gap-y-2">
                            @foreach ($temperatures as $temp)
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        name="recipient_filter[temperatures][]"
                                        value="{{ $temp->value }}"
                                        @checked(in_array($temp->value, old('recipient_filter.temperatures', [])))
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span class="text-sm text-gray-700">{{ $temp->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Lead Source --}}
                    <div class="border-t border-gray-100 pt-5">
                        <p class="mb-2.5 text-sm font-medium text-gray-700">Lead Source</p>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($sources as $source)
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        name="recipient_filter[sources][]"
                                        value="{{ $source->value }}"
                                        @checked(in_array($source->value, old('recipient_filter.sources', [])))
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span class="text-sm text-gray-700">{{ $source->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Enquiry Date Range --}}
                    <div class="border-t border-gray-100 pt-5">
                        <p class="mb-2.5 text-sm font-medium text-gray-700">Enquiry Date Range</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:max-w-lg">
                            <div>
                                <label for="filter_date_from" class="block mb-1 text-xs font-medium text-gray-600">From</label>
                                <input
                                    type="date"
                                    id="filter_date_from"
                                    name="recipient_filter[date_from]"
                                    value="{{ old('recipient_filter.date_from') }}"
                                    @class(['input-field', 'border-red-500' => $errors->has('recipient_filter.date_from')])
                                />
                                @error('recipient_filter.date_from')
                                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="filter_date_to" class="block mb-1 text-xs font-medium text-gray-600">To</label>
                                <input
                                    type="date"
                                    id="filter_date_to"
                                    name="recipient_filter[date_to]"
                                    value="{{ old('recipient_filter.date_to') }}"
                                    @class(['input-field', 'border-red-500' => $errors->has('recipient_filter.date_to')])
                                />
                                @error('recipient_filter.date_to')
                                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">Filters leads by their enquiry (created) date.</p>
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
                        <label for="campaign_scheduled_at" class="label">Schedule Send Time</label>
                        <input
                            type="datetime-local"
                            id="campaign_scheduled_at"
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
                <a href="{{ route('crm.communication.email.campaigns.index') }}" class="btn-secondary">
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

