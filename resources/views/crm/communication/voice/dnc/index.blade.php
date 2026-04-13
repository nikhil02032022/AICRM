<x-layouts.crm title="Do-Not-Call List">
    {{-- BRD: CRM-TC-009 — Do-Not-Call (DNC) list management view --}}
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Do-Not-Call (DNC) List</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Leads on this list will never receive calls, SMS, or email communications.
                    Removals require explicit re-consent before communication resumes.
                </p>
            </div>
            <a
                href="{{ route('crm.communication.voice.index') }}"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                aria-label="Back to Call Log"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Call Log
            </a>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif
        @if (session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif

        {{-- DPDP compliance notice --}}
        <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div class="text-sm text-amber-800">
                <strong class="font-semibold">DPDP Act 2023 Compliance:</strong>
                Opt-outs take effect immediately. The <code class="rounded bg-amber-100 px-1 py-0.5 font-mono text-xs">opt_out</code> flag is preserved even after DNC removal.
                The lead must separately provide re-consent before any outbound communication can resume.
            </div>
        </div>

        {{-- Search filter --}}
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('crm.communication.voice.dnc.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label for="dnc-search" class="block text-sm font-medium text-gray-700">
                            Search by name or reason
                        </label>
                        <input
                            id="dnc-search"
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="e.g. John Doe, wrong number…"
                            class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            aria-label="Search DNC list"
                        >
                    </div>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    >
                        Filter
                    </button>
                    @if ($search !== '')
                        <a
                            href="{{ route('crm.communication.voice.dnc.index') }}"
                            class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-500 shadow-sm transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                        >
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- DNC table --}}
        <div class="card">
            <div class="card-body overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="table-th">Lead</th>
                            <th class="table-th">Mobile (encrypted)</th>
                            <th class="table-th">DNC Reason</th>
                            <th class="table-th">Assigned Counsellor</th>
                            <th class="table-th">Added to DNC</th>
                            <th class="table-th">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($dncLeads as $lead)
                            <tr>
                                <td class="table-td font-medium">
                                    <a
                                        href="{{ route('crm.leads.show', $lead->uuid) }}"
                                        class="text-indigo-600 transition-colors duration-150 hover:text-indigo-800 hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-500/30 rounded"
                                    >
                                        {{ $lead->fullName() }}
                                    </a>
                                </td>
                                <td class="table-td text-gray-400">
                                    <span class="text-xs italic">Hidden (encrypted PII)</span>
                                </td>
                                <td class="table-td text-gray-700">
                                    {{ $lead->dnc_reason ?? '—' }}
                                </td>
                                <td class="table-td text-gray-500">
                                    {{ $lead->assignedCounsellor?->name ?? '—' }}
                                </td>
                                <td class="table-td text-gray-400">
                                    {{ $lead->dnc_at?->format('d M Y, H:i') ?? '—' }}
                                </td>
                                <td class="table-td">
                                    {{-- BRD: CRM-TC-009 — Admin-only removal from DNC --}}
                                    <div
                                        x-data="{ confirm: false }"
                                        @keydown.escape.window="confirm = false"
                                        class="relative"
                                    >
                                        <button
                                            type="button"
                                            @click="confirm = true"
                                            class="inline-flex items-center gap-1 rounded border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 transition-colors duration-150 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400/40"
                                            aria-label="Remove {{ $lead->fullName() }} from DNC list"
                                        >
                                            Remove from DNC
                                        </button>

                                        {{-- Confirmation popover --}}
                                        <div
                                            x-show="confirm"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            @click.outside="confirm = false"
                                            class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-gray-200 bg-white p-4 shadow-lg"
                                        >
                                            <p class="text-sm font-medium text-gray-800">Remove from DNC list?</p>
                                            <p class="mt-1 text-xs text-gray-500">
                                                Communication channels will stay blocked until the lead provides re-consent.
                                            </p>
                                            <div class="mt-3 flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    @click="confirm = false"
                                                    class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                                >
                                                    Cancel
                                                </button>
                                                <form
                                                    method="POST"
                                                    action="{{ route('crm.communication.voice.dnc.destroy', $lead->uuid) }}"
                                                    class="inline"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="rounded bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition-colors duration-150 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                                                    >
                                                        Confirm Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    <p class="text-sm font-medium text-gray-500">No leads on the DNC list</p>
                                    <p class="mt-0.5 text-xs text-gray-400">Leads added to DNC from the lead detail page will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $dncLeads->links() }}
                </div>
            </div>
        </div>

        {{-- How to add a lead to DNC --}}
        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-800">
            <strong class="font-semibold">How to add a lead to the DNC list:</strong>
            Open the lead's detail page and use the
            <span class="inline-flex items-center gap-0.5 rounded bg-indigo-100 px-1.5 py-0.5 font-mono text-xs">Add to DNC</span>
            action in the communication sidebar. A reason is required for DPDP audit purposes.
        </div>

    </div>
</x-layouts.crm>
