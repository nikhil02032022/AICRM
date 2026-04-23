{{-- BRD: CRM-AG-001 — Agent list: paginated table with status filter and search --}}
<x-layouts.crm title="Agents">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Agents & Channel Partners</h1>
                <p class="mt-1 text-sm text-gray-500">Manage agent profiles, referral codes, and commission structures.</p>
            </div>
            @can('crm.agents.create')
            <a href="{{ route('crm.agents.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Agent
            </a>
            @endcan
        </div>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    {{-- Search + filter --}}
    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500">
            <option value="">All Statuses</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
        </select>
        <button type="submit" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Filter</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Agreement</th>
                    <th class="px-4 py-3 text-left">Referral Code</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($agents as $agent)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $agent->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $agent->email }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            @if($agent->status->value === 'active') bg-green-100 text-green-700
                            @elseif($agent->status->value === 'inactive') bg-gray-100 text-gray-600
                            @else bg-red-100 text-red-700 @endif">
                            {{ $agent->status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $agent->agreement_start->format('d M Y') }}
                        @if($agent->agreement_end) – {{ $agent->agreement_end->format('d M Y') }} @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($agent->relationLoaded('referralCode') && $agent->referralCode)
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono">{{ $agent->referralCode->code }}</code>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('crm.agents.referral', $agent) }}" class="text-indigo-600 hover:underline text-xs">Referral</a>
                            <a href="{{ route('crm.agents.commission-structures.index', $agent) }}" class="text-indigo-600 hover:underline text-xs">Commission</a>
                            <a href="{{ route('crm.agents.edit', $agent) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No agents found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $agents->withQueryString()->links() }}
    </div>
</x-layouts.crm>
