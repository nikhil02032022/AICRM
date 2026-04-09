<x-layouts.crm>
    <x-slot:header>Embed Code & QR — {{ $form->name }}</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ route('crm.forms.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to Forms
        </a>
    </x-slot:headerActions>

    <div class="max-w-3xl space-y-6">

        {{-- Status badge --}}
        <div class="flex items-center gap-3">
            @if($form->is_active)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>
                    Active — accepting submissions
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                    Inactive — not accepting submissions
                </span>
            @endif
            <span class="text-sm text-gray-500">Source: <strong>{{ $form->source?->label() }}</strong></span>
        </div>

        {{-- Public URL --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
             x-data="{ copied: false }">
            <h3 class="mb-4 text-sm font-semibold text-gray-900">Public Form URL</h3>
            <div class="flex items-center gap-3">
                <code class="flex-1 rounded-lg bg-gray-50 border border-gray-200 px-4 py-2.5 text-sm text-gray-700 font-mono overflow-x-auto">{{ $form->publicUrl() }}</code>
                <button type="button"
                        @click="navigator.clipboard.writeText('{{ $form->publicUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :aria-label="copied ? 'Copied!' : 'Copy URL'">
                    <svg x-show="!copied" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184"/>
                    </svg>
                    <svg x-show="copied" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
                <a href="{{ $form->publicUrl() }}" target="_blank" rel="noopener noreferrer"
                   class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                    </svg>
                    Open
                </a>
            </div>
        </div>

        {{-- iFrame Embed Code --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
             x-data="{ copied: false }">
            <h3 class="mb-1 text-sm font-semibold text-gray-900">iFrame Embed Code</h3>
            <p class="mb-4 text-xs text-gray-500">Paste this snippet on your institution website to embed the form.</p>
            <div class="relative">
                <pre class="rounded-lg bg-gray-900 px-4 py-4 text-xs text-green-300 overflow-x-auto leading-relaxed font-mono" aria-label="Embed code snippet">{{ $embedSnippet }}</pre>
                <button type="button"
                        @click="navigator.clipboard.writeText(`{{ addslashes($embedSnippet) }}`); copied = true; setTimeout(() => copied = false, 2000)"
                        class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-md bg-gray-700 px-2.5 py-1.5 text-xs font-medium text-gray-200 hover:bg-gray-600 transition-colors cursor-pointer focus:outline-none"
                        :aria-label="copied ? 'Copied!' : 'Copy code'">
                    <svg x-show="!copied" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184"/>
                    </svg>
                    <svg x-show="copied" class="h-3.5 w-3.5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
        </div>

        {{-- QR Code --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-1 text-sm font-semibold text-gray-900">QR Code</h3>
            <p class="mb-4 text-xs text-gray-500">
                Print or display at events and walk-in desks. Scanning opens this form with UTM tracking pre-filled.
            </p>
            <div class="flex flex-wrap items-start gap-6">
                {{-- QR preview (served from API endpoint which requires auth) --}}
                <div class="flex-shrink-0 rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <img
                        src="{{ route('api.crm.crm.forms.qr', $form->uuid) }}"
                        alt="QR code for {{ $form->name }}"
                        width="180"
                        height="180"
                        loading="lazy"
                        class="h-[180px] w-[180px] rounded-lg"
                        onerror="this.style.display='none'; document.getElementById('qr-fallback').style.display='flex'"
                    >
                    <div id="qr-fallback" class="hidden h-[180px] w-[180px] items-center justify-center text-xs text-gray-400 rounded-lg bg-gray-100" aria-hidden="true">
                        QR Preview
                    </div>
                </div>
                <div class="flex flex-col gap-3 pt-1">
                    <p class="text-xs text-gray-600">
                        QR URL:<br>
                        <code class="mt-1 block rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 font-mono">{{ $form->qrTargetUrl() }}</code>
                    </p>
                    <a href="{{ route('api.crm.crm.forms.qr', $form->uuid) }}"
                       download="qr-{{ $form->slug }}.png"
                       class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 w-fit">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Download PNG
                    </a>
                </div>
            </div>
        </div>

    </div>
</x-layouts.crm>
