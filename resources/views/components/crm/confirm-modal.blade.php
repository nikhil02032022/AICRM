{{--
    Reusable Confirm / Alert Modal — A2A-CRM
    ─────────────────────────────────────────
    Usage:
        Place once per page, anywhere inside <x-layouts.crm>.
        Variant drives icon, colours, and default copy.

        Trigger buttons dispatch an Alpine.js custom event:
            $dispatch('confirm-delete',  { formId: 'my-form-id', itemName: 'Item Name' })
            $dispatch('confirm-launch',  { formId: 'my-form-id', itemName: 'Campaign Name' })
            $dispatch('confirm-cancel',  { formId: 'my-form-id', itemName: 'Session on 12 Apr' })
            $dispatch('confirm-warning', { formId: 'my-form-id', itemName: 'Config Name' })

    Props:
        variant       — delete | launch | cancel | warning (default: delete)
        title         — Override modal heading
        subtext       — Override the second line of descriptive text
        confirmLabel  — Override confirm button label
        cancelLabel   — Override cancel button label (default: Cancel)
--}}
@props([
    'variant'      => 'delete',
    'title'        => null,
    'subtext'      => null,
    'confirmLabel' => null,
    'cancelLabel'  => 'Cancel',
])

@php
    $cfg = match($variant) {
        'launch'  => [
            'title'        => 'Launch campaign?',
            'action'       => 'launch',
            'subtext'      => 'Messages will be dispatched immediately to all recipients in the segment.',
            'subtext2'     => 'Unsubscribed leads are automatically excluded.',
            'confirmLabel' => 'Yes, launch now',
            'iconBg'       => 'bg-emerald-100',
            'iconColor'    => 'text-emerald-600',
            'btnClass'     => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
            'icon'         => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z',
        ],
        'cancel'  => [
            'title'        => 'Cancel this item?',
            'action'       => 'cancel',
            'subtext'      => 'This action cannot be undone.',
            'subtext2'     => null,
            'confirmLabel' => 'Yes, cancel it',
            'iconBg'       => 'bg-amber-100',
            'iconColor'    => 'text-amber-600',
            'btnClass'     => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
            'icon'         => 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        'warning' => [
            'title'        => 'Are you sure?',
            'action'       => 'proceed with',
            'subtext'      => 'Please confirm you want to continue.',
            'subtext2'     => null,
            'confirmLabel' => 'Yes, proceed',
            'iconBg'       => 'bg-amber-100',
            'iconColor'    => 'text-amber-600',
            'btnClass'     => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
            'icon'         => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        ],
        default   => [   // delete
            'title'        => 'Delete this item?',
            'action'       => 'permanently delete',
            'subtext'      => 'This action cannot be undone and all associated data will be lost.',
            'subtext2'     => null,
            'confirmLabel' => 'Yes, delete',
            'iconBg'       => 'bg-red-100',
            'iconColor'    => 'text-red-600',
            'btnClass'     => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
            'icon'         => 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
        ],
    };

    $modalTitle   = $title        ?? $cfg['title'];
    $modalSubtext = $subtext      ?? $cfg['subtext'];
    $btnLabel     = $confirmLabel ?? $cfg['confirmLabel'];
    $subtext2     = $cfg['subtext2'] ?? null;
    $action       = $cfg['action'];
@endphp

<div
    x-data="{ open: false, formId: '', itemName: '' }"
    x-on:confirm-{{ $variant }}.window="
        open = true;
        formId = $event.detail.formId;
        itemName = $event.detail.itemName;
        $nextTick(() => $refs.cancelBtn.focus())
    "
    @keydown.escape.window="open = false"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title-{{ $variant }}"
        @click.self="open = false"
        style="display:none"
    >
        {{-- Panel --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            class="w-full max-w-md rounded-2xl bg-white shadow-xl ring-1 ring-gray-200 p-6"
            style="display:none"
        >
            {{-- Close button --}}
            <div class="flex justify-end -mt-1 -mr-1 mb-2">
                <button
                    type="button"
                    @click="open = false"
                    class="flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-gray-300"
                    aria-label="Close"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Icon --}}
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {{ $cfg['iconBg'] }}">
                <svg class="h-7 w-7 {{ $cfg['iconColor'] }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $cfg['icon'] }}"/>
                </svg>
            </div>

            {{-- Text --}}
            <div class="mt-4 text-center space-y-2">
                <h3 id="confirm-modal-title-{{ $variant }}" class="text-base font-bold text-gray-900">
                    {{ $modalTitle }}
                </h3>
                <p class="text-sm text-gray-500">
                    You are about to {{ $action }}
                    <strong class="font-semibold text-gray-800" x-text="'\u201c' + itemName + '\u201d'"></strong>.
                    {{ $modalSubtext }}
                </p>
                @if ($subtext2)
                    <p class="text-xs text-gray-400">{{ $subtext2 }}</p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                <button
                    x-ref="cancelBtn"
                    type="button"
                    @click="open = false"
                    class="inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 sm:w-auto cursor-pointer"
                >
                    {{ $cancelLabel }}
                </button>
                <button
                    type="button"
                    @click="document.getElementById(formId).submit(); open = false"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto cursor-pointer {{ $cfg['btnClass'] }}"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $cfg['icon'] }}"/>
                    </svg>
                    {{ $btnLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
