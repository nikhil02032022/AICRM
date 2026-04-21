<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? $branding['name'] }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --portal-primary: {{ $branding['primary_color'] }}; }
        .portal-btn-primary {
            background-color: var(--portal-primary);
            color: #ffffff;
        }
        .portal-btn-primary:hover { opacity: 0.9; }
        .portal-text-primary { color: var(--portal-primary); }
        .portal-border-primary { border-color: var(--portal-primary); }
        .portal-ring-primary:focus { outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--portal-primary) 30%, transparent); }
    </style>
</head>
<body class="h-full">

    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">

        {{-- Institution branding header --}}
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                @if ($branding['logo_path'])
                    <img
                        src="{{ asset($branding['logo_path']) }}"
                        alt="{{ $branding['name'] }}"
                        class="h-12 w-auto object-contain"
                        onerror="this.style.display='none'; document.getElementById('portal-name-fallback').style.display='block';"
                    />
                    <span id="portal-name-fallback" class="hidden text-2xl font-bold portal-text-primary">
                        {{ $branding['name'] }}
                    </span>
                @else
                    <span class="text-2xl font-bold portal-text-primary">{{ $branding['name'] }}</span>
                @endif
            </div>

            @isset($heading)
                <h2 class="mt-6 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
                    {{ $heading }}
                </h2>
            @endisset
        </div>

        {{-- Card --}}
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-sm rounded-lg sm:px-10 border border-gray-100">

                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-4">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('info'))
                    <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 p-4">
                        <p class="text-sm text-blue-700">{{ session('info') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4">
                        @foreach ($errors->all() as $error)
                            <p class="text-sm text-red-700">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>

        {{-- Footer --}}
        <p class="mt-6 text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} {{ $branding['name'] }}. All rights reserved.
        </p>

    </div>

</body>
</html>
