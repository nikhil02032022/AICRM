{{-- Portal navigation links — shared between mobile + desktop sidebar --}}
@php
    $navItems = [
        ['label' => 'Dashboard',       'route' => 'portal.dashboard',      'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['label' => 'My Applications', 'route' => 'portal.applications.index', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['label' => 'Documents',       'route' => 'portal.documents.index', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ['label' => 'Payments',        'route' => 'portal.payments.index',  'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
        ['label' => 'Appointments',    'route' => 'portal.appointments.index', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['label' => 'Chat',            'route' => 'portal.chat.index',      'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
        ['label' => 'Downloads',       'route' => 'portal.applications.index', 'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'],
    ];
@endphp

<nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
    @foreach ($navItems as $item)
        @php $isActive = Route::is($item['route']); @endphp
        @if (Route::has($item['route']))
            <a href="{{ route($item['route']) }}"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-md text-white transition-colors
                      {{ $isActive ? 'portal-nav-active' : 'portal-nav-hover' }}">
                <svg class="mr-3 h-5 w-5 shrink-0 text-white opacity-80 group-hover:opacity-100"
                     fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                </svg>
                {{ $item['label'] }}
            </a>
        @else
            <span class="group flex items-center px-3 py-2 text-sm font-medium rounded-md text-white opacity-50 cursor-not-allowed">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                </svg>
                {{ $item['label'] }}
            </span>
        @endif
    @endforeach
</nav>
