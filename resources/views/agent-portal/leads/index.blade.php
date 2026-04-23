{{-- BRD: CRM-AG-003 — Agent portal: submitted leads with status tracking --}}
<x-layouts.agent-portal-app title="My Leads">
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Leads</h1>
                <p class="mt-1 text-sm text-gray-500">All leads you have submitted and their current status.</p>
            </div>
            <a href="{{ route('agent-portal.leads.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Submit Lead
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Programme Interest</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Counsellor</th>
                        <th class="px-4 py-3 text-left">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($leads as $lead)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $lead->fullName() }}</div>
                            <div class="text-xs text-gray-500">{{ $lead->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $lead->programmeInterests->first()?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700">
                                {{ $lead->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs">
                            {{ $lead->assignedCounsellor?->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $lead->updated_at->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                            No leads submitted yet.
                            <a href="{{ route('agent-portal.leads.create') }}" class="text-indigo-600 hover:underline ml-1">Submit your first lead.</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $leads->links() }}</div>
    </div>
</x-layouts.agent-portal-app>
