<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Fallback polling if Echo/Pusher is unavailable (BRD: CRM-EC-019 Mitigation) --}}
    <meta http-equiv="refresh" content="30">
    <title>Queue Display — {{ $institution->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center">

    <div class="w-full max-w-2xl px-8 text-center">
        <p class="text-gray-400 text-lg mb-2">{{ $institution->name }}</p>
        <p class="text-gray-500 text-sm mb-12">{{ now()->format('d M Y') }}</p>

        <livewire:crm.counselling.queue-display :institution="$institution" />
    </div>

    @livewireScripts
</body>
</html>
