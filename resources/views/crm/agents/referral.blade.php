{{-- BRD: CRM-AG-002 — Agent referral code card with shareable link --}}
<x-layouts.crm title="Referral Code">
    <x-slot:header>
        <div class="flex items-center gap-3">
            <a href="{{ route('crm.agents.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h1 class="text-xl font-semibold text-gray-900">Referral Code — {{ $agent->name }}</h1>
        </div>
    </x-slot:header>

    <div class="mx-auto max-w-xl">
        @if($referralCode)
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5" x-data>
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Unique Referral Code</p>
                <p class="text-3xl font-mono font-bold text-indigo-600 tracking-wider">{{ $referralCode->code }}</p>
            </div>

            <hr class="border-gray-100">

            <div>
                <p class="text-xs font-medium text-gray-500 mb-1">Shareable Lead Capture Link</p>
                <div class="flex items-center gap-2">
                    <input type="text" readonly id="ref-url"
                           value="{{ url('/leads/capture?ref=' . $referralCode->code) }}"
                           class="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-mono text-gray-700 focus:outline-none">
                    <button type="button"
                            @click="navigator.clipboard.writeText(document.getElementById('ref-url').value); $el.textContent = 'Copied!'; setTimeout(()=>$el.textContent='Copy', 1500)"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Copy
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($referralCode->total_leads) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Leads Generated</p>
                </div>
                <div class="rounded-lg bg-green-50 p-4 text-center">
                    <p class="text-2xl font-bold text-green-700">{{ number_format($referralCode->total_conversions) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Conversions</p>
                </div>
            </div>

            <p class="text-xs text-gray-400 text-center">
                When a prospective student opens the link, their lead is automatically attributed to {{ $agent->name }}.
            </p>
        </div>
        @else
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-400">
            No referral code found. It should have been auto-generated when the agent was created.
        </div>
        @endif
    </div>
</x-layouts.crm>
