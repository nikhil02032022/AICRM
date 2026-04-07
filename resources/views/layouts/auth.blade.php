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
<body class="min-h-screen bg-gradient-to-br from-primary-700 to-primary-900 font-sans antialiased">

    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            {{-- Card --}}
            <div class="rounded-2xl bg-white shadow-2xl">

                {{-- Header --}}
                <div class="rounded-t-2xl bg-primary-800 px-8 py-8 text-center">
                    <h1 class="text-2xl font-bold text-white">A2A CRM</h1>
                    <p class="mt-1 text-sm text-primary-200">Admissions-2-Alumni Platform</p>
                </div>

                {{-- Content --}}
                <div class="px-8 py-8">
                    {{ $slot }}
                </div>

            </div>

            <p class="mt-6 text-center text-xs text-primary-200">
                &copy; {{ date('Y') }} MEETCS Pvt. Ltd. All rights reserved.
            </p>

        </div>
    </div>

    @livewireScripts

</body>
</html>
