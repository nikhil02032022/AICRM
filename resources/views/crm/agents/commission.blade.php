{{-- BRD: AG-006 — Agent Commission Management: create, approve, reject, and pay commissions --}}
<x-layouts.crm title="Agent Commissions">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Agent Commissions</h1>
                <p class="mt-1 text-sm text-gray-500">Manage commission records for channel partners — create, approve, reject, and mark as paid.</p>
            </div>
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'create-commission')"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Commission
            </button>
        </div>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Status filter tabs --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'paid' => 'Paid'] as $val => $label)
            <a href="{{ route('crm.agents.commission.index', array_merge(request()->query(), ['status' => $val])) }}"
               class="rounded-full border px-3 py-1 text-xs font-medium transition
                   {{ request('status', 'all') === $val ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Commissions Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Agent</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lead</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Type</th>
                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-500">Amount (₹)</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($commissions as $commission)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $commission->agent?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $commission->lead?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded border border-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700 bg-gray-50">
                                {{ Str::title($commission->commission_type ?? '—') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                            {{ number_format($commission->commission_amount ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @php $color = $commission->status->color(); @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ $commission->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($commission->status->value === 'pending')
                                    <form action="{{ route('crm.agents.commission.update', $commission->uuid) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="rounded border border-green-300 px-2 py-0.5 text-xs font-medium text-green-700 hover:bg-green-50 transition">
                                            Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('crm.agents.commission.update', $commission->uuid) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="rounded border border-red-300 px-2 py-0.5 text-xs font-medium text-red-700 hover:bg-red-50 transition">
                                            Reject
                                        </button>
                                    </form>
                                @elseif($commission->status->value === 'approved')
                                    <form action="{{ route('crm.agents.commission.update', $commission->uuid) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="pay">
                                        <button type="submit" class="rounded border border-indigo-300 px-2 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50 transition">
                                            Mark Paid
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $commission->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                            No commission records found. Click <span class="font-semibold">Add Commission</span> to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($commissions->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $commissions->links() }}
            </div>
        @endif
    </div>

    {{-- Create Commission Modal --}}
    <div
        x-data="{ open: false, commType: '{{ old('commission_type', 'fixed') }}' }"
        x-on:open-modal.window="if ($event.detail === 'create-commission') open = true"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        style="display:none"
        x-cloak
    >
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl" @click.stop>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Add Commission Record</h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('crm.agents.commission.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Agent User ID</label>
                        <input type="number" name="agent_user_id" required min="1"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            value="{{ old('agent_user_id') }}" placeholder="User ID">
                        @error('agent_user_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="commission_lead_uuid">Lead</label>
                        <select id="commission_lead_uuid" name="lead_uuid" required
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
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Commission Type</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="commission_type" value="fixed" x-model="commType"> Fixed Amount
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="commission_type" value="percentage" x-model="commType"> Percentage
                        </label>
                    </div>
                    @error('commission_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Fixed amount field --}}
                <div x-show="commType === 'fixed'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fixed Amount (₹)</label>
                    <input type="number" name="commission_amount" min="0" step="0.01"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        value="{{ old('commission_amount') }}" placeholder="0.00">
                    @error('commission_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Percentage fields --}}
                <div x-show="commType === 'percentage'" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Percentage Rate (%)</label>
                        <input type="number" name="percentage_rate" min="0" max="100" step="0.01"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            value="{{ old('percentage_rate') }}" placeholder="0.00">
                        @error('percentage_rate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Base Amount (₹)</label>
                        <input type="number" name="base_amount" min="0" step="0.01"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            value="{{ old('base_amount') }}" placeholder="0.00">
                        @error('base_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Commission</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.crm>
