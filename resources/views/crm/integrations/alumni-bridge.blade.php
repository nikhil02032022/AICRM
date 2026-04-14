{{-- BRD: EI-008 — Alumni Bridge: trigger ERP alumni module sync for converted leads --}}
<x-layouts.crm title="Alumni Bridge">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Alumni Bridge</h1>
                <p class="mt-1 text-sm text-gray-500">Sync converted leads to the A2A ERP Alumni module and track referral activity.</p>
            </div>
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'trigger-alumni-bridge')"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Trigger Alumni Bridge
            </button>
        </div>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Bridge Logs Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lead</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">ERP Student ID</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">ERP Alumni ID</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Referral Code</th>
                    <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">Referrals</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Triggered</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $log->lead?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ $log->erp_student_id ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ $log->erp_alumni_id ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($log->referral_code)
                                <span class="rounded bg-indigo-50 px-2 py-0.5 text-xs font-mono font-semibold text-indigo-700">
                                    {{ $log->referral_code }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-700">
                                {{ $log->referrals_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php $color = $log->status->color(); @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ $log->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                            No Alumni Bridge records yet. Click <span class="font-semibold">Trigger Alumni Bridge</span> to sync a student.
                        </td>
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

    {{-- Trigger Modal --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="if ($event.detail === 'trigger-alumni-bridge') open = true"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        style="display:none"
        x-cloak
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.stop>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Trigger Alumni Bridge</h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('crm.integrations.alumni-bridge.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="ab_erp_student_id">ERP Student ID</label>
                    <input type="text" id="ab_erp_student_id" name="erp_student_id" required placeholder="ERP Student Master ID"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        value="{{ old('erp_student_id') }}">
                    @error('erp_student_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="ab_lead_uuid">Lead</label>
                    <select id="ab_lead_uuid" name="lead_uuid" required
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">— Select lead —</option>
                        @foreach($leads as $lead)
                            <option value="{{ $lead->uuid }}" {{ old('lead_uuid') === $lead->uuid ? 'selected' : '' }}>
                                {{ trim($lead->first_name . ' ' . $lead->last_name) }} ({{ $lead->mobile }})
                            </option>
                        @endforeach
                    </select>
                    @error('lead_uuid')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Trigger</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.crm>
