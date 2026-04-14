{{-- BRD: DM-007 — Aadhaar eKYC: initiate KYC session and OTP verification --}}
<x-layouts.crm title="Aadhaar eKYC">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Aadhaar eKYC</h1>
                <p class="mt-1 text-sm text-gray-500">Initiate and track Aadhaar-based KYC verification for leads. Aadhaar numbers are never stored.</p>
            </div>
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'initiate-kyc')"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Initiate KYC
            </button>
        </div>
    </x-slot:header>

    {{-- DPDP notice --}}
    <div class="mb-4 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-xs text-amber-800">
        <strong>DPDP Act 2023:</strong> Aadhaar numbers are processed only in-transit and are never stored in this system per UIDAI guidelines.
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- KYC Logs Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lead</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Transaction ID</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Name Match</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Action</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Initiated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $log->lead?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-600">
                            {{ $log->transaction_id ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @php $color = $log->status->color(); @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ $log->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @if($log->name_match === true)
                                <span class="text-green-600 font-semibold">✓ Yes</span>
                            @elseif($log->name_match === false)
                                <span class="text-red-600 font-semibold">✗ No</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($log->status->value === 'otp_sent')
                                <button
                                    type="button"
                                    x-data
                                    @click="$dispatch('open-modal', 'verify-otp-{{ $log->uuid }}')"
                                    class="inline-flex items-center rounded border border-indigo-300 px-2.5 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                                >
                                    Verify OTP
                                </button>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>

                    {{-- OTP Verify Modal per-log --}}
                    @if($log->status->value === 'otp_sent')
                        <tr class="hidden">
                            <td colspan="6">
                                <div
                                    x-data="{ open: false }"
                                    x-on:open-modal.window="if ($event.detail === 'verify-otp-{{ $log->uuid }}') open = true"
                                    x-on:keydown.escape.window="open = false"
                                    x-show="open"
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                                    style="display:none"
                                    x-cloak
                                >
                                    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl" @click.stop>
                                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Verify OTP — {{ $log->lead?->full_name ?? 'Lead' }}</h2>
                                        <form action="{{ route('crm.integrations.aadhaar-ekyc.verify-otp', $log->uuid) }}" method="POST" class="space-y-4">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1" for="otp_{{ $log->uuid }}">6-digit OTP</label>
                                                <input
                                                    type="text"
                                                    id="otp_{{ $log->uuid }}"
                                                    name="otp"
                                                    required
                                                    maxlength="6"
                                                    pattern="\d{6}"
                                                    placeholder="Enter OTP"
                                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 tracking-widest text-center text-lg"
                                                >
                                            </div>
                                            <div class="flex justify-end gap-3">
                                                <button type="button" @click="open = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                                                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Verify</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">No KYC sessions yet. Click <span class="font-semibold">Initiate KYC</span> to begin.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Initiate KYC Modal --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="if ($event.detail === 'initiate-kyc') open = true"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        style="display:none"
        x-cloak
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.stop>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Initiate Aadhaar eKYC</h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('crm.integrations.aadhaar-ekyc.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="lead_uuid_kyc">Lead</label>
                    <select id="lead_uuid_kyc" name="lead_uuid" required
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">— Select lead —</option>
                        @foreach($leads as $lead)
                            <option value="{{ $lead->uuid }}" {{ old('lead_uuid') === $lead->uuid ? 'selected' : '' }}>
                                {{ trim($lead->first_name . ' ' . $lead->last_name) }} ({{ $lead->mobile }})
                            </option>
                        @endforeach
                    </select>
                    @error('lead_uuid')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <p class="rounded-md border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs text-indigo-700">
                    The lead's Aadhaar number will be entered directly on the UIDAI OTP page and is never transmitted to this CRM.
                </p>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Initiate KYC</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.crm>
