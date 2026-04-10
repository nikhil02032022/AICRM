@props(['type' => 'info', 'message' => null])

@if($message)
@php
    $styles = match($type) {
        'success' => ['bg-green-50 border-green-300 text-green-800',   'text-green-500',  'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        'warning' => ['bg-amber-50 border-amber-300 text-amber-800',   'text-amber-500',  'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        'error'   => ['bg-red-50 border-red-300 text-red-800',         'text-red-500',    'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z'],
        default   => ['bg-indigo-50 border-indigo-300 text-indigo-800','text-indigo-500', 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z'],
    };
@endphp
<div role="alert" class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $styles[0] }}">
    <svg class="mt-0.5 h-4.5 w-4.5 flex-shrink-0 {{ $styles[1] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $styles[2] }}"/>
    </svg>
    <span>{{ $message }}</span>
</div>
@endif
