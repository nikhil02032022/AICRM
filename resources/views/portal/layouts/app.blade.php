<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ ($title ?? '') ? $title . ' — ' : '' }}{{ $branding['name'] }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --portal-primary: {{ $branding['primary_color'] }}; }
        .portal-header       { background-color: var(--portal-primary); }
        .portal-nav-active   { background-color: color-mix(in srgb, var(--portal-primary) 85%, #000); }
        .portal-nav-hover:hover { background-color: color-mix(in srgb, var(--portal-primary) 80%, #000); }
        .portal-btn-primary  { background-color: var(--portal-primary); color: #ffffff; }
        .portal-btn-primary:hover { opacity: 0.9; }
        .portal-text-primary { color: var(--portal-primary); }
        .portal-border-primary { border-color: var(--portal-primary); }
        .portal-ring-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--portal-primary) 30%, transparent);
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">

    {{-- Mobile sidebar overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
        @click="sidebarOpen = false"
        style="display: none;"
    ></div>

    {{-- Mobile sidebar --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition ease-in-out duration-200 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-200 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col portal-header lg:hidden"
        style="display: none;"
    >
        <div class="flex h-16 items-center justify-between px-4">
            @if ($branding['logo_path'])
                <img src="{{ asset($branding['logo_path']) }}" alt="{{ $branding['name'] }}"
                     class="h-8 w-auto object-contain brightness-0 invert" />
            @else
                <span class="text-white font-bold text-lg truncate">{{ $branding['name'] }}</span>
            @endif
            <button @click="sidebarOpen = false" class="text-white hover:text-gray-200 ml-2">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @include('portal.layouts._nav')
    </div>

    {{-- Desktop sidebar --}}
    <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col portal-header">
        <div class="flex h-16 items-center px-4">
            @if ($branding['logo_path'])
                <img src="{{ asset($branding['logo_path']) }}" alt="{{ $branding['name'] }}"
                     class="h-8 w-auto object-contain brightness-0 invert" />
            @else
                <span class="text-white font-bold text-lg truncate">{{ $branding['name'] }}</span>
            @endif
        </div>
        @include('portal.layouts._nav')
    </div>

    {{-- Main content --}}
    <div class="lg:pl-64 flex flex-col min-h-screen">

        {{-- Top bar --}}
        <div class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:px-6">

            {{-- Mobile menu button --}}
            <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <div class="flex-1">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>

            {{-- Applicant name --}}
            @if (isset($applicant))
                <span class="text-sm text-gray-600 hidden sm:block">
                    {{ $applicant->full_name ?? $applicant->first_name ?? '' }}
                </span>
            @endif

            {{-- Logout --}}
            <form method="POST" action="{{ route('portal.logout') }}">
                @csrf
                <button type="submit"
                        class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                    Sign out
                </button>
            </form>
        </div>

        {{-- Flash messages --}}
        <div class="px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 p-4">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>

        <footer class="py-4 px-4 sm:px-6 lg:px-8 border-t border-gray-100">
            <p class="text-xs text-gray-400 text-center">
                &copy; {{ date('Y') }} {{ $branding['name'] }}. All rights reserved.
            </p>
        </footer>
    </div>

</body>
</html>
