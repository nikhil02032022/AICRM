<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false }">
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
<body class="min-h-screen bg-gray-50 font-sans antialiased">

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

                {{-- Lead Scoring — BRD: CRM-LQ-001, CRM-LQ-008 (Group D) --}}
                @can('crm.leads.view')
                <a href="{{ route('crm.scoring.source-quality') }}"
                   aria-current="{{ request()->routeIs('crm.scoring.source-quality') ? 'page' : 'false' }}"
                   class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('crm.scoring.source-quality') ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-200 hover:bg-indigo-800/60 hover:text-white' }}">
                    <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                    </svg>
                    Lead Scoring
                </a>
                @endcan

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

                <div class="my-4 border-t" style="border-color: rgba(99,102,241,0.2)"></div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-indigo-500">Coming Soon</p>

                {{-- Modules not yet built — Phase 1+ --}}
                @foreach([
                    ['Applications',   'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z'],
                    ['Tasks',          'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['Communications', 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z'],
                    ['Analytics',      'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
                ] as [$label, $iconPath])
                    <div class="mb-0.5 flex cursor-not-allowed select-none items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-indigo-500"
                         title="{{ $label }} — Coming soon" aria-disabled="true">
                        <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                        </svg>
                        <span class="flex-1">{{ $label }}</span>
                        <span class="rounded bg-indigo-900/80 px-1.5 py-0.5 text-xs text-indigo-500">Soon</span>
                    </div>
                @endforeach

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
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6" id="main-content">

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