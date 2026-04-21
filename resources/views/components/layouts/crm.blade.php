<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false, applicationsMenuOpen: {{ request()->routeIs('crm.applications.pipeline.board') || request()->routeIs('crm.applications.list') || request()->routeIs('crm.applications.show') || request()->routeIs('crm.applications.transition') || request()->routeIs('crm.applications.forms.*') || request()->routeIs('crm.applications.drafts.*') || request()->routeIs('crm.applications.programmes.*') || request()->routeIs('crm.conversions.*') ? 'true' : 'false' }} }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }} — A2A CRM</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-screen overflow-hidden bg-gray-50 font-sans antialiased">

    {{-- Mobile sidebar backdrop --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-indigo-950/60 backdrop-blur-sm lg:hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display:none"
        aria-hidden="true"
    ></div>

    <div class="flex h-screen overflow-hidden">

        {{-- ── Sidebar ── --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            style="background-color: #1e1b4b;"
        >
            {{-- Logo --}}
            <div class="flex h-16 flex-shrink-0 items-center gap-3 border-b px-5" style="border-color: rgba(99,102,241,0.2)">
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-500/20 ring-1 ring-indigo-400/25">
                    <svg class="h-5 w-5 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold leading-none text-white">A2A CRM</p>
                    <p class="mt-0.5 truncate text-xs leading-none text-indigo-400">Admissions-2-Alumni</p>
                </div>
                {{-- Mobile close --}}
                <button @click="sidebarOpen = false" class="text-indigo-400 hover:text-white transition-colors lg:hidden" aria-label="Close navigation">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Institution badge --}}
            @auth
            <div class="flex-shrink-0 border-b px-5 py-3" style="border-color: rgba(99,102,241,0.2)">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-500">Institution</p>
                <p class="mt-0.5 truncate text-sm font-medium text-indigo-200">{{ auth()->user()->institution?->name ?? 'MEETCS Platform' }}</p>
            </div>
            @endauth

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Main navigation">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Main</p>

                <a href="{{ route('dashboard') }}"
                   aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('dashboard') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                    Dashboard
                </a>

                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">CRM Modules</p>

                {{-- Leads — Sprint 1: Live --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.leads.index') }}"
                   aria-current="{{ request()->routeIs('crm.leads.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.leads.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    Leads
                </a>
                @endcan

                {{-- Applications — BRD: CRM-AP-008 (Sprint 3 Group N) --}}
                @can('crm.applications.view')
                @php
                    $applicationsMenuActive = request()->routeIs('crm.applications.pipeline.board')
                        || request()->routeIs('crm.applications.list')
                        || request()->routeIs('crm.applications.show')
                        || request()->routeIs('crm.applications.transition')
                        || request()->routeIs('crm.applications.forms.*')
                        || request()->routeIs('crm.applications.drafts.*')
                        || request()->routeIs('crm.applications.programmes.*')
                        || request()->routeIs('crm.conversions.*');
                @endphp
                <div class="mb-0.5" x-data>
                    <button
                        type="button"
                        @click="applicationsMenuOpen = !applicationsMenuOpen"
                        class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500/70
                               {{ $applicationsMenuActive ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}"
                        :aria-expanded="applicationsMenuOpen ? 'true' : 'false'"
                        aria-controls="applications-sidebar-submenu"
                    >
                        <span class="flex items-center gap-3">
                            <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 5.25h6.75v5.25H4.5V5.25Zm8.25 0h6.75v5.25h-6.75V5.25Zm-8.25 8.25h6.75v5.25H4.5V13.5Zm8.25 0h6.75v5.25h-6.75V13.5Z" />
                            </svg>
                            Applications
                        </span>
                        <svg class="h-4 w-4 flex-shrink-0 transition-transform duration-150" :class="applicationsMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div
                        id="applications-sidebar-submenu"
                        x-show="applicationsMenuOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="mt-1 space-y-0.5"
                        style="display:none"
                    >
                        <a href="{{ route('crm.applications.pipeline.board') }}"
                           aria-current="{{ request()->routeIs('crm.applications.pipeline.board') ? 'page' : 'false' }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 pl-10 text-sm font-medium transition-colors duration-150
                                  {{ request()->routeIs('crm.applications.pipeline.board') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                            Pipeline Board
                        </a>

                        <a href="{{ route('crm.applications.list') }}"
                           aria-current="{{ request()->routeIs('crm.applications.list') ? 'page' : 'false' }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 pl-10 text-sm font-medium transition-colors duration-150
                                  {{ request()->routeIs('crm.applications.list') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                            Applications List
                        </a>

                        <a href="{{ route('crm.applications.forms.index') }}"
                           aria-current="{{ request()->routeIs('crm.applications.forms.*') ? 'page' : 'false' }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 pl-10 text-sm font-medium transition-colors duration-150
                                  {{ request()->routeIs('crm.applications.forms.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                            Application Forms
                        </a>

                        {{-- BRD: CRM-AP-016 — ERP conversion log and retry --}}
                        @can('crm.applications.view')
                        <a href="{{ route('crm.conversions.index') }}"
                           aria-current="{{ request()->routeIs('crm.conversions.*') ? 'page' : 'false' }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 pl-10 text-sm font-medium transition-colors duration-150
                                  {{ request()->routeIs('crm.conversions.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                            ERP Conversions
                        </a>
                        @endcan
                    </div>
                </div>
                @endcan

                {{-- Web Forms — Sprint Group B --}}
                @can('crm.forms.view')
                <a href="{{ route('crm.forms.index') }}"
                   aria-current="{{ request()->routeIs('crm.forms.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.forms.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                    Web Forms
                </a>
                @endcan

                @can('crm.campaigns.manage')
                <a href="{{ route('crm.marketing.landing-pages.index') }}"
                   aria-current="{{ request()->routeIs('crm.marketing.landing-pages.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.marketing.landing-pages.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5M3.75 9.75h16.5m-16.5 4.5h9.75m-9.75 4.5h9.75M17.25 13.5l1.5 1.5 3-3"/>
                    </svg>
                    Landing Pages
                </a>
                @endcan

                @can('crm.campaigns.manage')
                <a href="{{ route('crm.marketing.kiosk.index') }}"
                   aria-current="{{ request()->routeIs('crm.marketing.kiosk.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.marketing.kiosk.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75A2.25 2.25 0 0 1 6 4.5h12a2.25 2.25 0 0 1 2.25 2.25v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75Zm3 0h10.5m-10.5 3h10.5m-10.5 3h6"/>
                    </svg>
                    Walk-in Kiosk
                </a>
                @endcan

                @can('crm.chat-widget.manage')
                <a href="{{ route('crm.marketing.chat-widget.index') }}"
                   aria-current="{{ request()->routeIs('crm.marketing.chat-widget.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.marketing.chat-widget.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75h6.75m-6.75 3h4.5m4.867 5.654A9.959 9.959 0 0 1 12 20.25a9.959 9.959 0 0 1-5.992-1.846L3.75 19.5l1.096-2.258A9.96 9.96 0 0 1 2.25 10.5a9.75 9.75 0 1 1 15.742 7.742Z"/>
                    </svg>
                    Website Chatbot
                </a>
                @endcan

                @can('crm.campaigns.manage')
                <a href="{{ route('crm.marketing.attribution.index') }}"
                   aria-current="{{ request()->routeIs('crm.marketing.attribution.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.marketing.attribution.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 17.25V6.75m0 10.5a2.25 2.25 0 1 0 0 4.5m0-4.5a2.25 2.25 0 1 1 0 4.5m9-16.5v13.5m0-13.5a2.25 2.25 0 1 0 0-4.5m0 4.5a2.25 2.25 0 1 1 0-4.5m9 16.5V6.75m0 10.5a2.25 2.25 0 1 0 0 4.5m0-4.5a2.25 2.25 0 1 1 0 4.5"/>
                    </svg>
                    Attribution
                </a>
                @endcan

                @can('crm.campaigns.manage')
                <a href="{{ route('crm.marketing.cost-tracking.index') }}"
                   aria-current="{{ request()->routeIs('crm.marketing.cost-tracking.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.marketing.cost-tracking.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6m10.5-7.5v15a1.5 1.5 0 0 1-3 0v-15m-3 15a1.5 1.5 0 0 1-3 0v-15m-3 15a1.5 1.5 0 0 1-3 0v-15"/>
                    </svg>
                    Cost Tracking
                </a>
                @endcan

                @can('crm.campaigns.manage')
                <a href="{{ route('crm.marketing.automation-workflows.index') }}"
                   aria-current="{{ request()->routeIs('crm.marketing.automation-workflows.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.marketing.automation-workflows.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h6m3 0h6M4.5 12h3m6 0h6M4.5 18h6m3 0h6"/>
                    </svg>
                    Automation Workflows
                </a>
                @endcan

                {{-- Bulk Import — BRD: CRM-LC-012 (Group C) --}}
                @can('crm.leads.import')
                <a href="{{ route('crm.imports.index') }}"
                   aria-current="{{ request()->routeIs('crm.imports.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.imports.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                    </svg>
                    Bulk Import
                </a>
                @endcan

                {{-- AI & Qualification — BRD: CRM-LQ-003, CRM-LQ-009, CRM-LQ-010 (Group I) --}}
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">AI &amp; Qualification</p>

                {{-- Lead Scoring — BRD: CRM-LQ-001, CRM-LQ-008 (Group D) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.scoring.source-quality') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.source-quality') || request()->routeIs('crm.leads.ai-score') || request()->routeIs('crm.leads.ai-score.recalculate') || request()->routeIs('crm.leads.churn-risk') || request()->routeIs('crm.leads.churn-risk.recalculate') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.source-quality') || request()->routeIs('crm.leads.ai-score') || request()->routeIs('crm.leads.ai-score.recalculate') || request()->routeIs('crm.leads.churn-risk') || request()->routeIs('crm.leads.churn-risk.recalculate') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                    </svg>
                    Lead Scoring
                </a>
                @endcan

                {{-- Qualification Questionnaires — BRD: CRM-LQ-009 (Group I) --}}
                @can('crm.questionnaires.manage')
                <a href="{{ route('crm.scoring.questionnaires.index') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.questionnaires.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.questionnaires.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m5.25 2.25a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z"/>
                    </svg>
                    Qualification Questionnaires
                </a>
                @endcan

                {{-- Daily Priority Leads — BRD: CRM-AI-005 (Group I) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.scoring.priority-leads') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.priority-leads') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.priority-leads') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h7.5M3 12h4.5M3 16.5h6"/>
                    </svg>
                    Daily Priority Leads
                </a>
                @endcan

                {{-- Enrolment Forecasting — BRD: CRM-AI-008 (Group I) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.scoring.enrolment-forecasts') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.enrolment-forecasts') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.enrolment-forecasts') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25m0 0 3-3m-3 3 3 3m13.5-3V3m0 11.25-3-3m3 3-3 3"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 18.75h6"/>
                    </svg>
                    Enrolment Forecasts
                </a>
                @endcan

                {{-- Anomaly Alerts — BRD: CRM-AI-009 (Group I) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.scoring.anomaly-alerts') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.anomaly-alerts') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.anomaly-alerts') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.051 3.378c.866-1.5 3.032-1.5 3.898 0l7.354 12.748Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5h.007v.008H12V16.5Z"/>
                    </svg>
                    Anomaly Alerts
                </a>
                @endcan

                {{-- Nurture Journey Builder — BRD: CRM-AI-010 (Group I) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.scoring.nba-journeys') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.nba-journeys') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.nba-journeys') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 10.5 13.821l7.071-7.071M5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 17.25V6.75A2.25 2.25 0 0 1 5.25 4.5Z"/>
                    </svg>
                    Nurture Journeys
                </a>
                @endcan

                {{-- AI Usage Logs — BRD: CRM-AI-012 (Group I) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.scoring.ai-usage-logs') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.ai-usage-logs') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.ai-usage-logs') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7.5 4.5h9A1.5 1.5 0 0 1 18 6v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 18V6a1.5 1.5 0 0 1 1.5-1.5Z"/>
                    </svg>
                    AI Usage Logs
                </a>
                @endcan

                {{-- Counsellor Workload — BRD: CRM-EC-008 (Group E) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.counsellors.workload') }}"
                   aria-current="{{ request()->routeIs('crm.counsellors.workload') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.counsellors.workload') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    Counsellor Workload
                </a>
                @endcan

                {{-- Gamification Dashboard — BRD: CRM-EC-010 (Group J) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.gamification.index') }}"
                   aria-current="{{ request()->routeIs('crm.gamification.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.gamification.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0"/>
                    </svg>
                    Leaderboard & Gamification
                </a>
                @endcan

                {{-- ── Integrations — Group L (DM-006, DM-007, EI-008, EI-010) ── --}}
                @canany(['crm.integrations.manage'])
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Integrations</p>

                {{-- DigiLocker — BRD: DM-006 --}}
                @can('crm.integrations.manage')
                <a href="{{ route('crm.integrations.digilocker.index') }}"
                   aria-current="{{ request()->routeIs('crm.integrations.digilocker.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.integrations.digilocker.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                    </svg>
                    DigiLocker
                </a>
                @endcan

                {{-- Aadhaar eKYC — BRD: DM-007 --}}
                @can('crm.integrations.manage')
                <a href="{{ route('crm.integrations.aadhaar-ekyc.index') }}"
                   aria-current="{{ request()->routeIs('crm.integrations.aadhaar-ekyc.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.integrations.aadhaar-ekyc.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                    </svg>
                    Aadhaar eKYC
                </a>
                @endcan

                {{-- Alumni Bridge — BRD: EI-008 --}}
                @can('crm.integrations.manage')
                <a href="{{ route('crm.integrations.alumni-bridge.index') }}"
                   aria-current="{{ request()->routeIs('crm.integrations.alumni-bridge.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.integrations.alumni-bridge.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                    </svg>
                    Alumni Bridge
                </a>
                @endcan

                {{-- LMS Enrolment — BRD: EI-010 --}}
                @can('crm.integrations.manage')
                <a href="{{ route('crm.integrations.lms-enrolment.index') }}"
                   aria-current="{{ request()->routeIs('crm.integrations.lms-enrolment.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.integrations.lms-enrolment.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                    </svg>
                    LMS Enrolment
                </a>
                @endcan
                @endcanany

                {{-- ── Agents — Group L (AG-006, AG-008) ── --}}
                @canany(['crm.agents.commissions.view', 'crm.agents.comms.view'])
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Agents</p>

                {{-- Agent Commissions — BRD: AG-006 --}}
                @can('crm.agents.commissions.view')
                <a href="{{ route('crm.agents.commission.index') }}"
                   aria-current="{{ request()->routeIs('crm.agents.commission.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.agents.commission.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Agent Commissions
                </a>
                @endcan

                {{-- Agent Bulk Comms — BRD: AG-008 --}}
                @can('crm.agents.comms.view')
                <a href="{{ route('crm.agents.comms.index') }}"
                   aria-current="{{ request()->routeIs('crm.agents.comms.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.agents.comms.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                    </svg>
                    Agent Bulk Comms
                </a>
                @endcan
                @endcanany

                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Settings</p>

                {{-- Integrations — BRD: CRM-SA-010 (Group C) --}}
                @can('crm.integrations.view')
                <a href="{{ route('crm.settings.integrations.index') }}"
                   aria-current="{{ request()->routeIs('crm.settings.integrations.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.settings.integrations.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                    Integrations
                </a>
                @endcan

                {{-- Assignment Config — BRD: CRM-EC-006 (Group E) --}}
                @can('crm.settings.manage')
                <a href="{{ route('crm.settings.assignment-config') }}"
                   aria-current="{{ request()->routeIs('crm.settings.assignment-config*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.settings.assignment-config*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    Assignment Config
                </a>
                @endcan

                {{-- Scoring Config — BRD: CRM-LQ-005 (Group D) --}}
                @can('crm.settings.scoring')
                <a href="{{ route('crm.scoring.config') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.config') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.config') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/>
                    </svg>
                    Scoring Config
                </a>
                @endcan

                {{-- Sender Domains — BRD: CRM-CC-003 --}}
                @can('crm.settings.manage')
                <a href="{{ route('crm.settings.sender-domains.index') }}"
                   aria-current="{{ request()->routeIs('crm.settings.sender-domains.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.settings.sender-domains.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253M3.284 14.253A8.959 8.959 0 0 1 3 12c0-.778.099-1.533.284-2.253"/>
                    </svg>
                    Sender Domains
                </a>
                @endcan

                {{-- Custom Fields — BRD: CRM-EC-005 (Group K) --}}
                @can('crm.settings.custom-fields.view')
                <a href="{{ route('crm.settings.custom-fields.index') }}"
                   aria-current="{{ request()->routeIs('crm.settings.custom-fields.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.settings.custom-fields.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
                    </svg>
                    Custom Fields
                </a>
                @endcan

                {{-- Workflow Templates — BRD: CRM-SA-007 (Group K) --}}
                @can('crm.settings.workflow-templates.view')
                <a href="{{ route('crm.settings.workflow-templates.index') }}"
                   aria-current="{{ request()->routeIs('crm.settings.workflow-templates.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.settings.workflow-templates.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/>
                    </svg>
                    Workflow Templates
                </a>
                @endcan

                {{-- IVR Config — BRD: CRM-CC-019 --}}
                @can('crm.settings.manage')
                <a href="{{ route('crm.settings.ivr.index') }}"
                   aria-current="{{ request()->routeIs('crm.settings.ivr.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.settings.ivr.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                    </svg>
                    IVR Config
                </a>
                @endcan


                {{-- ── Analytics — Group K ── --}}
                @if(auth()->user()->can('crm.analytics.view') || auth()->user()->can('crm.reports.view') || auth()->user()->can('crm.reports.manage'))
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Analytics</p>
                @endif

                {{-- Conversion Report — BRD: CRM-AP-017 (Analytics) --}}
                @can('crm.analytics.view')
                <a href="{{ route('crm.analytics.conversion-report') }}"
                   aria-current="{{ request()->routeIs('crm.analytics.conversion-report') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.analytics.conversion-report') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Conversion Report
                </a>
                @endcan

                {{-- Conversion Rates — BRD: CRM-AP-019 --}}
                @can('crm.analytics.view')
                <a href="{{ route('crm.analytics.conversion-rates') }}"
                   aria-current="{{ request()->routeIs('crm.analytics.conversion-rates') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.analytics.conversion-rates') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                    </svg>
                    Conversion Rates
                </a>
                @endcan

                {{-- Custom Reports — BRD: CRM-AR-018 (Group K) --}}
                @can('crm.reports.view')
                <a href="{{ route('crm.reports.custom.index') }}"
                   aria-current="{{ request()->routeIs('crm.reports.custom.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.reports.custom.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                    </svg>
                    Custom Reports
                </a>
                @endcan

                {{-- Report Scheduler — BRD: CRM-AR-020 (Group K) --}}
                @can('crm.reports.view')
                <a href="{{ route('crm.reports.scheduler.index') }}"
                   aria-current="{{ request()->routeIs('crm.reports.scheduler.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.reports.scheduler.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Report Scheduler
                </a>
                @endcan

                {{-- ── Finance — Group O (BRD: CRM-FM-001 to CRM-FM-013) ── --}}
                @canany(['fee_dashboard.view', 'payments.view', 'fee_structure.manage', 'scholarship.category.manage', 'scholarship.award.submit', 'scholarship.award.approve.manager', 'scholarship.award.approve.finance', 'installment.plan.manage'])
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Finance</p>

                @can('fee_dashboard.view')
                <a href="{{ route('crm.payments.fee-dashboard.index') }}"
                   aria-current="{{ request()->routeIs('crm.payments.fee-dashboard.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.payments.fee-dashboard.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    Fee Dashboard
                </a>
                @endcan

                @can('fee_structure.manage')
                <a href="{{ route('crm.payments.fee-structures.index') }}"
                   aria-current="{{ request()->routeIs('crm.payments.fee-structures.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.payments.fee-structures.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    Fee Structures
                </a>
                @endcan

                @can('payments.view')
                <a href="{{ route('crm.payments.refunds.index') }}"
                   aria-current="{{ request()->routeIs('crm.payments.refunds.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.payments.refunds.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    Refund Requests
                </a>
                @endcan

                {{-- BRD: CRM-FM-006 to CRM-FM-009 — Group P --}}
                @can('scholarship.category.manage')
                <a href="{{ route('crm.scholarships.categories.index') }}"
                   aria-current="{{ request()->routeIs('crm.scholarships.categories.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scholarships.categories.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    Scholarship Categories
                </a>
                @endcan
                @canany(['scholarship.award.submit','scholarship.award.approve.manager','scholarship.award.approve.finance'])
                <a href="{{ route('crm.scholarships.awards.index') }}"
                   aria-current="{{ request()->routeIs('crm.scholarships.awards.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scholarships.awards.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    Scholarship Approvals
                </a>
                @endcanany
                @can('installment.plan.manage')
                <a href="{{ route('crm.payments.installments.index') }}"
                   aria-current="{{ request()->routeIs('crm.payments.installments.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.payments.installments.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    Installment Plans
                </a>
                @endcan
                @endcanany

                {{-- BRD: CRM-DM-001 to CRM-DM-010 — Documents (Group P) --}}
                @canany(['document.checklist.manage','document.review','document.bulk_download'])
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Documents</p>

                @can('document.checklist.manage')
                <a href="{{ route('crm.documents.checklists.index') }}"
                   aria-current="{{ request()->routeIs('crm.documents.checklists.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.documents.checklists.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    Checklists
                </a>
                @endcan
                @can('document.review')
                <a href="{{ route('crm.documents.review.index') }}"
                   aria-current="{{ request()->routeIs('crm.documents.review.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.documents.review.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    Document Review
                </a>
                @endcan
                @endcanany

                {{-- ── Communication Engine — Group F ── --}}
                @canany(['crm.communication.send', 'crm.communication.templates.manage', 'crm.campaigns.send'])
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Communication</p>

                {{-- Unified Inbox — BRD: CRM-CC-021 --}}
                @can('crm.communication.send')
                <a href="{{ route('crm.inbox.index') }}"
                   aria-current="{{ request()->routeIs('crm.inbox.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.inbox.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/>
                    </svg>
                    Inbox
                </a>
                @endcan

                {{-- WhatsApp — BRD: CRM-CC-011 --}}
                @can('crm.communication.send')
                <a href="{{ route('crm.communication.whatsapp.index') }}"
                   aria-current="{{ request()->routeIs('crm.communication.whatsapp.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.communication.whatsapp.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                    </svg>
                    WhatsApp
                </a>
                @endcan

                {{-- Call Log — BRD: CRM-CC-017 --}}
                @can('crm.communication.send')
                <a href="{{ route('crm.communication.voice.index') }}"
                   aria-current="{{ request()->routeIs('crm.communication.voice.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.communication.voice.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                    </svg>
                    Call Log
                </a>
                @endcan

                {{-- Call Centre Performance — BRD: CRM-TC-007 --}}
                @can('crm.voice.performance')
                <a href="{{ route('crm.communication.voice.performance') }}"
                   aria-current="{{ request()->routeIs('crm.communication.voice.performance') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.communication.voice.performance') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                    </svg>
                    Performance
                </a>
                @endcan

                {{-- Templates — BRD: CRM-CC-001 --}}
                @can('crm.communication.templates.manage')
                <a href="{{ route('crm.communication.templates.index') }}"
                   aria-current="{{ request()->routeIs('crm.communication.templates.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.communication.templates.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    Templates
                </a>
                @endcan

                {{-- Email Campaigns — BRD: CRM-CC-002 --}}
                @can('crm.campaigns.send')
                <a href="{{ route('crm.communication.email.campaigns.index') }}"
                   aria-current="{{ request()->routeIs('crm.communication.email.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.communication.email.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                    Email Campaigns
                </a>
                @endcan

                {{-- SMS Campaigns — BRD: CRM-CC-006 --}}
                @can('crm.campaigns.send')
                <a href="{{ route('crm.communication.sms.campaigns.index') }}"
                   aria-current="{{ request()->routeIs('crm.communication.sms.campaigns.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.communication.sms.campaigns.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                    </svg>
                    SMS Campaigns
                </a>
                @endcan

                {{-- DLT Templates — BRD: CRM-CC-008 --}}
                @can('crm.communication.send')
                <a href="{{ route('crm.communication.sms.dlt.templates.index') }}"
                   aria-current="{{ request()->routeIs('crm.communication.sms.dlt.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.communication.sms.dlt.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75m-6.75 5.25h13.5A2.25 2.25 0 0 0 21.75 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Zm10.5-11.25h.008v.008h-.008V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>
                    DLT Templates
                </a>
                @endcan
                @endcanany

                {{-- ── Admin — Group K ── --}}
                @can('crm.admin.system-health.view')
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Admin</p>

                {{-- System Health — BRD: CRM-SA-011 (Group K) --}}
                <a href="{{ route('crm.admin.system-health.index') }}"
                   aria-current="{{ request()->routeIs('crm.admin.system-health.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.admin.system-health.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 6 0m-6 0H3.375a1.125 1.125 0 0 1-1.125-1.125V10.5a1.125 1.125 0 0 1 1.125-1.125h.375m13.125 4.875a3 3 0 0 0 3-3m-3 3H18.625a1.125 1.125 0 0 0 1.125-1.125V10.5a1.125 1.125 0 0 0-1.125-1.125h-.375m-13.5 0h13.5m-13.5 0V6a2.25 2.25 0 0 1 2.25-2.25h9a2.25 2.25 0 0 1 2.25 2.25v4.125"/>
                    </svg>
                    System Health
                </a>
                @endcan

                {{-- Tasks — BRD: Group R --}}
                @can('crm.tasks.index')
                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Tasks</p>
                <a href="{{ route('crm.tasks.index') }}"
                   aria-current="{{ request()->routeIs('crm.tasks.*') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.tasks.*') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Tasks
                </a>
                @endcan

            {{-- User footer --}}
            @auth
            <div class="flex-shrink-0 border-t p-4" style="border-color: rgba(99,102,241,0.2)">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white select-none">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-indigo-400">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="cursor-pointer text-indigo-500 transition-colors hover:text-white" aria-label="Sign out">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </aside>

        {{-- ── Main area ── --}}
        <div class="flex flex-1 flex-col overflow-hidden">

            {{-- Topbar --}}
            <header class="flex h-16 flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 shadow-sm sm:px-6">
                {{-- Left: hamburger + page title --}}
                <div class="flex items-center gap-3">
                    <button
                        @click="sidebarOpen = true"
                        class="cursor-pointer rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 lg:hidden"
                        aria-label="Open navigation"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                        </svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-800">{{ $header ?? '' }}</h1>
                </div>

                {{-- Right: slot actions + notifications + avatar --}}
                <div class="flex items-center gap-2 sm:gap-3">
                    {{ $headerActions ?? '' }}

                    <button class="cursor-pointer rounded-md p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500" aria-label="Notifications">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                    </button>

                    @auth
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white select-none" title="{{ auth()->user()->name }}">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    @endauth
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6" id="main-content" style="scrollbar-gutter: stable">

                {{-- Flash: success --}}
                @if (session()->has('success'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-init="setTimeout(() => show = false, 4500)"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        role="alert"
                        class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800 shadow-sm"
                        style="display:none"
                    >
                        <svg class="h-5 w-5 flex-shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <span class="flex-1 font-medium">{{ session('success') }}</span>
                        <button @click="show = false" class="cursor-pointer text-green-600 transition-colors hover:text-green-800" aria-label="Dismiss">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                {{-- Flash: error --}}
                @if (session()->has('error'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        role="alert"
                        class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 text-sm text-red-800 shadow-sm"
                        style="display:none"
                    >
                        <svg class="h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                        <span class="flex-1 font-medium">{{ session('error') }}</span>
                        <button @click="show = false" class="cursor-pointer text-red-600 transition-colors hover:text-red-800" aria-label="Dismiss">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
    @livewireScripts

</body>
</html>