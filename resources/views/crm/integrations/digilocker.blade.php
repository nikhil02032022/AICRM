{{-- BRD: DM-006 — DigiLocker Integration: retrieve, verify, and store official documents --}}
<x-layouts.crm title="DigiLocker Documents">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">DigiLocker Documents</h1>
                <p class="mt-1 text-sm text-gray-500">Request and verify official documents via DigiLocker for enrolled leads.</p>
            </div>
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'initiate-digilocker')"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Initiate Request
            </button>
        </div>
    </x-slot:header>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Documents Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lead</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Document Type</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Verified At</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Requested</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($documents as $doc)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $doc->lead?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ Str::title(str_replace('_', ' ', $doc->document_type)) }}
                        </td>
                        <td class="px-4 py-3">
                            @php $color = $doc->status->color(); @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ $doc->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $doc->verified_at?->format('d M Y, H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $doc->created_at->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                            No DigiLocker document requests yet. Click <span class="font-semibold">Initiate Request</span> to start.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($documents->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $documents->links() }}
            </div>
        @endif
    </div>

    {{-- Initiate Request Modal --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="if ($event.detail === 'initiate-digilocker') open = true"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        style="display:none"
        x-cloak
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.stop>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Initiate DigiLocker Request</h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form action="{{ route('crm.integrations.digilocker.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="lead_uuid">Lead</label>
                    <select
                        id="lead_uuid"
                        name="lead_uuid"
                        required
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                    >
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="document_type">Document Type</label>
                    <select
                        id="document_type"
                        name="document_type"
                        required
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                    >
                        <option value="">— Select —</option>
                        <option value="aadhaar">Aadhaar Card</option>
                        <option value="pan">PAN Card</option>
                        <option value="marksheet_10">Class 10 Marksheet</option>
                        <option value="marksheet_12">Class 12 Marksheet</option>
                        <option value="degree_certificate">Degree Certificate</option>
                        <option value="migration_certificate">Migration Certificate</option>
                        <option value="transfer_certificate">Transfer Certificate</option>
                    </select>
                    @error('document_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="consent_record_id">Consent Record ID</label>
                    <input
                        type="text"
                        id="consent_record_id"
                        name="consent_record_id"
                        required
                        placeholder="Consent record reference"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        value="{{ old('consent_record_id') }}"
                    >
                    <p class="mt-1 text-xs text-gray-500">DPDP: Consent must be captured before requesting documents.</p>
                    @error('consent_record_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.crm>
