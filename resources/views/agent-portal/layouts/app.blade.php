<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Agent Portal' }} — A2A CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-900">
    {{-- Top navigation --}}
    <nav class="bg-white border-b border-gray-200 shadow-sm">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <div class="flex items-center gap-6">
                    <span class="text-sm font-bold text-indigo-600 tracking-tight">Agent Portal</span>
                    <a href="{{ route('agent-portal.dashboard') }}"
                       class="text-sm font-medium text-gray-600 hover:text-gray-900 {{ request()->routeIs('agent-portal.dashboard') ? 'text-indigo-600' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('agent-portal.leads.index') }}"
                       class="text-sm font-medium text-gray-600 hover:text-gray-900 {{ request()->routeIs('agent-portal.leads.*') ? 'text-indigo-600' : '' }}">
                        My Leads
                    </a>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">{{ $authAgent?->name ?? '' }}</span>
                    <form method="POST" action="{{ route('agent-portal.logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-red-600 transition">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- Page content --}}
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>
</body>
</html>
