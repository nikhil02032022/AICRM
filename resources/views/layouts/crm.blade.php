<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }} — A2A CRM</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite: Tailwind CSS + Alpine.js -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire styles -->
    @livewireStyles
</head>
<body class="min-h-screen overflow-x-hidden bg-gray-50 font-sans antialiased">

    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside class="hidden w-64 flex-shrink-0 bg-primary-800 text-white md:flex md:flex-col">
            {{-- Logo --}}
            <div class="flex h-16 items-center justify-center border-b border-primary-700 px-4">
                <span class="text-xl font-bold tracking-tight">A2A CRM</span>
            </div>

            {{-- Institution name --}}
            @auth
            <div class="border-b border-primary-700 px-4 py-3 text-xs text-primary-300">
                {{ auth()->user()->institution?->name ?? 'Institution' }}
            </div>
            @endauth

            {{-- Navigation --}}
            <nav class="flex-1 space-y-1 px-3 py-4">
                {{ $sidebar ?? '' }}
            </nav>

            {{-- User footer --}}
            @auth
            <div class="border-t border-primary-700 p-4">
                <div class="text-sm font-medium text-white">{{ auth()->user()->name }}</div>
                <div class="text-xs text-primary-300">{{ auth()->user()->getRoleNames()->first() }}</div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                        class="text-xs text-primary-400 hover:text-white transition">
                        Sign out
                    </button>
                </form>
            </div>
            @endauth
        </aside>

        {{-- Main content --}}
        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Top bar --}}
            <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">
                {{-- Page title --}}
                <h1 class="text-lg font-semibold text-gray-800">{{ $header ?? '' }}</h1>

                {{-- Right actions --}}
                <div class="flex items-center gap-4">
                    {{ $headerActions ?? '' }}
                </div>
            </header>

            {{-- Page content --}}
            <main class="min-w-0 flex-1 overflow-x-hidden bg-gray-50 p-6">
                {{-- Flash messages --}}
                @if (session()->has('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         class="mb-4 flex items-center rounded-lg bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="ml-auto text-green-600 hover:text-green-800">&times;</button>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div x-data="{ show: true }" x-show="show"
                         class="mb-4 flex items-center rounded-lg bg-red-50 border border-red-200 p-4 text-red-800 text-sm">
                        <span>{{ session('error') }}</span>
                        <button @click="show = false" class="ml-auto text-red-600 hover:text-red-800">&times;</button>
                    </div>
                @endif

                {{ $slot }}
            </main>

        </div>
    </div>

    @livewireScripts

</body>
</html>
