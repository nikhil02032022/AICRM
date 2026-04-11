<x-layouts.crm>
    <x-slot:header>Website Chatbot</x-slot:header>

    @php
        $totalCaptured = (int) ($metrics['captured_count'] ?? $chatLeads->total());
        $liveAgentCount = (int) ($metrics['live_agent_count'] ?? 0);
        $resolvedCount = (int) ($metrics['resolved_count'] ?? 0);
        $pendingCount = (int) ($metrics['pending_count'] ?? 0);
        $avgFirstResponse = (float) ($metrics['avg_first_response_minutes'] ?? 0.0);

        $embedSnippet = sprintf(
            '<iframe src="%s" title="%s Live Chat" width="100%%" height="640" style="border:0;max-width:420px;"></iframe>',
            $embedUrl,
            $institution->name
        );
    @endphp

    <div
        class="space-y-6 pb-8"
        x-data="{
            search: '',
            statusFilter: '',
            replyModalOpen: false,
            selectedLeadUuid: '',
            selectedLeadName: '',
            openReplyModal(leadUuid, leadName) {
                this.selectedLeadUuid = leadUuid;
                this.selectedLeadName = leadName;
                this.replyModalOpen = true;
                this.$nextTick(() => { document.getElementById('shared-reply-message')?.focus(); });
            },
            rowVisible(searchText, statusValue) {
                const q = this.search.trim().toLowerCase();
                const textMatch = q === '' || searchText.includes(q);
                const statusMatch = this.statusFilter === '' || this.statusFilter === statusValue;
                return textMatch && statusMatch;
            }
        }"
    >
        {{-- ═══ Page Header ══════════════════════════════════════════════════════ --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600">CRM-LC-006 · Operator Workspace</span>
                </div>
                <h1 class="mt-2 text-2xl font-bold leading-tight text-gray-900 sm:text-3xl">Admissions Chat Queue</h1>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">Review incoming chatbot leads, update handoff status, send agent replies, and route to counsellors.</p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2 sm:flex-nowrap">
                <span class="hidden rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm sm:inline-flex">
                    {{ $institution->name }}
                </span>
                <a
                    href="{{ $embedUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Preview Widget
                </a>
                <a
                    href="{{ route('crm.marketing.chat-widget.index') }}"
                    class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Refresh
                </a>
            </div>
        </div>

        {{-- ═══ KPI Metric Cards ══════════════════════════════════════════════════ --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            {{-- Total Captured --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-gray-500">Total Captured</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100">
                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($totalCaptured) }}</p>
                <p class="mt-1 text-xs text-gray-400">All time leads</p>
            </div>

            {{-- Live Agent --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-emerald-700">Live Agent</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tracking-tight text-emerald-900">{{ number_format($liveAgentCount) }}</p>
                <p class="mt-1 text-xs text-emerald-600">In progress now</p>
            </div>

            {{-- Pending Agent --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-amber-700">Pending Agent</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tracking-tight text-amber-900">{{ number_format($pendingCount) }}</p>
                <p class="mt-1 text-xs text-amber-600">Awaiting response</p>
            </div>

            {{-- Resolved --}}
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-sky-700">Resolved</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-100">
                        <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tracking-tight text-sky-900">{{ number_format($resolvedCount) }}</p>
                <p class="mt-1 text-xs text-sky-600">Completed sessions</p>
            </div>

            {{-- Avg Response --}}
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-5 shadow-sm sm:col-span-2 xl:col-span-1">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-violet-700">Avg Response</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100">
                        <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5 6.75 6 12h4.5m0 0L14.25 0 9.75 6H14.25" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5 21l-6.75-7.5h4.5L3.75 13.5Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tracking-tight text-violet-900">
                    {{ number_format($avgFirstResponse, 1) }}<span class="ml-0.5 text-lg font-medium">m</span>
                </p>
                <p class="mt-1 text-xs text-violet-600">First reply time</p>
            </div>
        </div>

        {{-- ═══ Embed Deployment Kit ═════════════════════════════════════════════ --}}
        <div
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            x-data="{ copied: false, copySnippet() { navigator.clipboard.writeText(@js($embedSnippet)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                        </svg>
                        <h2 class="text-base font-semibold text-gray-900">Embed Deployment Kit</h2>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Place this snippet on any page of your website to activate the chatbot widget.</p>
                </div>

                <button
                    type="button"
                    @click="copySnippet()"
                    aria-label="Copy embed snippet to clipboard"
                    class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    :class="{ 'border-emerald-400 bg-emerald-50 text-emerald-700': copied }"
                >
                    <template x-if="!copied">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                        </svg>
                    </template>
                    <template x-if="copied">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </template>
                    <span x-text="copied ? 'Copied!' : 'Copy Snippet'"></span>
                </button>
            </div>

            {{-- Code Block --}}
            <div class="mt-4 overflow-hidden rounded-lg border border-gray-800 bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-700 px-4 py-2">
                    <div class="flex gap-1.5" aria-hidden="true">
                        <span class="h-3 w-3 rounded-full bg-red-400 opacity-80"></span>
                        <span class="h-3 w-3 rounded-full bg-yellow-400 opacity-80"></span>
                        <span class="h-3 w-3 rounded-full bg-green-400 opacity-80"></span>
                    </div>
                    <span class="text-xs text-gray-400">HTML Embed</span>
                </div>
                <div class="p-4">
                    <code class="block whitespace-pre-wrap break-all text-xs leading-relaxed text-emerald-300">{{ $embedSnippet }}</code>
                </div>
            </div>

            {{-- Ingestion Endpoint --}}
            <div class="mt-4 flex items-start gap-3 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
                <div>
                    <p class="text-xs font-semibold text-indigo-800">Lead Ingestion Endpoint</p>
                    <p class="mt-0.5 break-all text-xs text-indigo-700">{{ $submitUrl }}</p>
                </div>
            </div>
        </div>

        {{-- ═══ Captured Chatbot Leads ════════════════════════════════════════════ --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            {{-- Section Header + Filters --}}
            <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Captured Chatbot Leads</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Review, update handoff status, and send replies to incoming leads.</p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:w-[34rem]">
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3" aria-hidden="true">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                            </div>
                            <label for="lead-search" class="sr-only">Search lead name or session ID</label>
                            <input
                                id="lead-search"
                                x-model="search"
                                type="search"
                                placeholder="Search lead or session…"
                                class="block min-h-11 w-full rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                        </div>

                        <label for="status-filter" class="sr-only">Filter by status</label>
                        <select
                            id="status-filter"
                            x-model="statusFilter"
                            class="min-h-11 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-44"
                        >
                            <option value="">All statuses</option>
                            <option value="captured">Captured</option>
                            <option value="pending_agent">Pending Agent</option>
                            <option value="live_agent">Live Agent</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Empty State --}}
            @if($chatLeads->isEmpty())
                <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50">
                        <svg class="h-7 w-7 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-gray-900">No chat leads captured yet</p>
                    <p class="mt-1 max-w-xs text-sm text-gray-500">Open the preview link and submit a test enquiry to validate your widget setup.</p>
                    <a
                        href="{{ $embedUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-5 inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Open Preview
                    </a>
                </div>

            @else
                {{-- Column Header Row (desktop only) --}}
                <div class="hidden border-b border-gray-100 bg-gray-50 px-6 py-2 lg:grid lg:grid-cols-12 lg:gap-x-4">
                    <div class="lg:col-span-4 text-xs font-medium uppercase tracking-wide text-gray-400">Lead</div>
                    <div class="lg:col-span-2 text-xs font-medium uppercase tracking-wide text-gray-400">Status</div>
                    <div class="lg:col-span-3 text-xs font-medium uppercase tracking-wide text-gray-400">Update Status</div>
                    <div class="lg:col-span-3 text-right text-xs font-medium uppercase tracking-wide text-gray-400">Actions</div>
                </div>

                {{-- Lead Rows --}}
                <div class="divide-y divide-gray-100">
                    @foreach($chatLeads as $chatLead)
                        @php
                            $leadName    = $chatLead->lead ? $chatLead->lead->fullName() : 'Lead unavailable';
                            $statusValue = (string) ($chatLead->handoff_status ?? 'captured');
                            $searchValue = strtolower($leadName . ' ' . $chatLead->session_id);
                            $initial     = mb_strtoupper(mb_substr($leadName, 0, 1));

                            $badgeClass = match($statusValue) {
                                'live_agent' => 'inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800',
                                'resolved'   => 'inline-flex items-center rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium text-sky-800',
                                default      => 'inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800',
                            };
                        @endphp

                        <article
                            x-show="rowVisible(@js($searchValue), @js($statusValue))"
                            class="grid items-center gap-x-5 gap-y-3 px-6 py-4 transition-colors duration-150 hover:bg-gray-50/70 lg:grid-cols-12"
                        >
                            {{-- Col 1 — Lead identity (4 cols) --}}
                            <div class="lg:col-span-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700" aria-hidden="true">
                                        {{ $initial }}
                                    </span>
                                    <div class="min-w-0">
                                        @if($chatLead->lead)
                                            <a
                                                href="{{ route('crm.leads.show', $chatLead->lead->uuid) }}"
                                                class="block truncate text-sm font-semibold text-indigo-600 transition-colors duration-150 hover:text-indigo-800 hover:underline focus:outline-none focus:underline"
                                            >
                                                {{ $leadName }}
                                            </a>
                                        @else
                                            <p class="truncate text-sm font-semibold text-gray-700">{{ $leadName }}</p>
                                        @endif
                                        <p class="mt-0.5 truncate text-xs text-gray-400">
                                            {{ $chatLead->session_id }} &middot; {{ $chatLead->created_at?->format('d M Y') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Col 2 — Status badge only (2 cols) --}}
                            <div class="lg:col-span-2">
                                <span class="{{ $badgeClass }} whitespace-nowrap">
                                    {{ str($statusValue)->replace('_', ' ')->title() }}
                                </span>
                            </div>

                            {{-- Col 3 — Update status form, inline (3 cols) --}}
                            <div class="min-w-0 lg:col-span-3">
                                <form
                                    method="POST"
                                    action="{{ route('crm.marketing.chat-widget.handoff', $chatLead->uuid) }}"
                                    class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-2"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <label for="handoff-{{ $chatLead->uuid }}" class="sr-only">Update handoff status for {{ $leadName }}</label>
                                    <select
                                        id="handoff-{{ $chatLead->uuid }}"
                                        name="handoff_status"
                                        class="min-h-10 w-full min-w-0 rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                        @foreach(['captured' => 'Captured', 'pending_agent' => 'Pending Agent', 'live_agent' => 'Live Agent', 'resolved' => 'Resolved'] as $val => $lbl)
                                            <option value="{{ $val }}" @selected($statusValue === $val)>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                    <button
                                        type="submit"
                                        class="inline-flex min-h-10 shrink-0 cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                    >
                                        Save
                                    </button>
                                </form>
                            </div>

                            {{-- Col 4 — Actions (3 cols) --}}
                            <div class="flex flex-wrap items-center gap-2 lg:col-span-3 lg:flex-col lg:items-stretch lg:justify-center xl:flex-row xl:items-center xl:justify-end">
                                <button
                                    type="button"
                                    @click="openReplyModal(@js($chatLead->uuid), @js($leadName))"
                                    class="inline-flex min-h-10 shrink-0 cursor-pointer items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-colors duration-150 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                    Reply
                                </button>

                                @if($chatLead->lead)
                                    <a
                                        href="{{ route('crm.leads.show', $chatLead->lead->uuid) }}"
                                        class="inline-flex min-h-10 shrink-0 cursor-pointer items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        View Lead
                                    </a>
                                @else
                                    <span class="inline-flex min-h-10 cursor-not-allowed items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-400">
                                        View Lead
                                    </span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($chatLeads->hasPages())
                    <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                        {{ $chatLeads->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- ═══ Single Shared Reply Modal ════════════════════════════════════════ --}}
        <template x-if="replyModalOpen">
            <div
                class="fixed inset-0 z-50"
                role="dialog"
                aria-modal="true"
                :aria-label="`Send reply to ${selectedLeadName}`"
                @keydown.escape.window="replyModalOpen = false"
            >
                <div class="flex h-full items-center justify-center p-4">
                    {{-- Backdrop --}}
                    <button
                        type="button"
                        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
                        @click="replyModalOpen = false"
                        aria-label="Close modal"
                        tabindex="-1"
                    ></button>

                    {{-- Modal Panel --}}
                    <div
                        class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                    >
                        {{-- Modal Header --}}
                        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100">
                                    <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                </span>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Send Agent Reply</h3>
                                    <p class="mt-0.5 text-xs text-gray-500" x-text="selectedLeadName"></p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="replyModalOpen = false"
                                aria-label="Close reply modal"
                                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Modal Form --}}
                        <form
                            method="POST"
                            :action="`{{ url('/crm/marketing/chat-widget') }}/${selectedLeadUuid}/reply`"
                            class="p-5"
                        >
                            @csrf
                            <div>
                                <label for="shared-reply-message" class="block text-sm font-medium text-gray-700">
                                    Message <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <textarea
                                    id="shared-reply-message"
                                    name="message"
                                    rows="4"
                                    required
                                    placeholder="Type your reply to this lead's chat transcript…"
                                    class="mt-2 block w-full resize-none rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                                <p class="mt-1.5 text-xs text-gray-400">This reply will be appended to the chat transcript for counsellor review.</p>
                            </div>

                            <div class="mt-5 flex gap-3 sm:justify-end">
                                <button
                                    type="button"
                                    @click="replyModalOpen = false"
                                    class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 sm:flex-none"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 sm:flex-none"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                    Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.crm>
