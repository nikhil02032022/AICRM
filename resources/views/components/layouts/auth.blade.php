<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Login' }} — A2A CRM</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-screen overflow-hidden font-sans antialiased">

    {{-- ── Background gradient + decorative blobs ── --}}
    <div class="fixed inset-0 bg-gradient-to-br from-indigo-950 via-indigo-900 to-violet-950">
        <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-indigo-700/40 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-violet-700/40 blur-3xl"></div>
        <div class="absolute top-1/3 right-1/4 h-64 w-64 rounded-full bg-primary-500/10 blur-2xl"></div>
    </div>

    {{-- ── Two-column layout ── --}}
    <div class="relative z-10 flex h-screen overflow-hidden">

        {{-- Left: branding panel (lg+) --}}
        <div class="hidden lg:flex lg:w-5/12 xl:w-2/5 flex-col items-start justify-between px-10 xl:px-14 py-8 text-white overflow-hidden">

            {{-- Logo --}}
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20 shadow-lg backdrop-blur-sm">
                    <svg class="h-6 w-6 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                    </svg>
                </div>
                <div>
                    <span class="text-lg font-bold tracking-tight">A2A CRM</span>
                    <span class="ml-2 text-xs text-indigo-400">by MEETCS</span>
                </div>
            </div>

            {{-- Headline --}}
            <h1 class="mb-3 text-3xl font-extrabold leading-tight">
                Intelligent<br>Admissions.<br>
                <span class="text-indigo-300">End-to-end.</span>
            </h1>
            <p class="mb-4 max-w-sm text-sm leading-relaxed text-indigo-200">
                A unified platform to capture leads, nurture prospects, and convert enquiries into enrolled students — seamlessly integrated with the A2A ERP ecosystem.
            </p>

            {{-- Feature bullets --}}
            <div class="w-full max-w-sm space-y-2">
                @foreach([
                    ['AI lead scoring &amp; next best action',         'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z', 'bg-violet-500/30'],
                    ['Omnichannel: Email · SMS · WhatsApp',            'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z', 'bg-blue-500/30'],
                    ['DPDP Act 2023 built-in compliance',              'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z', 'bg-green-500/30'],
                    ['Multi-institution · Multi-campus tenancy',       'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z', 'bg-amber-500/30'],
                ] as [$label, $path, $iconBg])
                    <div class="flex items-center gap-3 rounded-xl bg-white/6 px-3 py-2.5 ring-1 ring-white/8">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg {{ $iconBg }}">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                            </svg>
                        </div>
                        <p class="text-xs text-indigo-100">{!! $label !!}</p>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-indigo-500">&copy; {{ date('Y') }} MEETCS Pvt. Ltd. All rights reserved.</p>
        </div>

        {{-- Right: form panel --}}
        <div class="flex w-full lg:w-7/12 xl:w-3/5 items-center justify-center overflow-y-auto px-6 py-8">
            <div class="w-full max-w-md">

                {{-- Mobile logo --}}
                <div class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-white">A2A CRM</h1>
                </div>

                {{-- Card --}}
                <div class="rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                    {{-- Top gradient accent bar --}}
                    <div class="h-1.5 w-full rounded-t-2xl bg-gradient-to-r from-primary-500 via-primary-600 to-violet-600"></div>
                    <div class="px-8 py-8">
                        {{ $slot }}
                    </div>
                </div>

                <p class="mt-5 text-center text-xs text-indigo-400 lg:hidden">
                    &copy; {{ date('Y') }} MEETCS Pvt. Ltd.
                </p>
            </div>
        </div>
    </div>

    @livewireScripts

</body>
</html>
