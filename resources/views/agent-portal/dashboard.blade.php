{{-- BRD: CRM-AG-003 — Agent portal dashboard with KPI stats --}}
<x-layouts.agent-portal-app title="Dashboard">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ $agent->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Here's an overview of your referral performance.</p>
        </div>

        {{-- KPI cards --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl bg-white border border-gray-200 p-5 shadow-sm text-center">
                <p class="text-3xl font-bold text-gray-900">{{ number_format($metrics['total_leads']) }}</p>
                <p class="mt-1 text-xs text-gray-500">Leads Submitted</p>
            </div>
            <div class="rounded-xl bg-white border border-gray-200 p-5 shadow-sm text-center">
                <p class="text-3xl font-bold text-green-700">{{ number_format($metrics['total_conversions']) }}</p>
                <p class="mt-1 text-xs text-gray-500">Enrolled</p>
            </div>
            <div class="rounded-xl bg-white border border-gray-200 p-5 shadow-sm text-center">
                <p class="text-3xl font-bold text-amber-600">{{ $metrics['conversion_rate'] }}%</p>
                <p class="mt-1 text-xs text-gray-500">Conversion Rate</p>
            </div>
            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-5 shadow-sm text-center">
                <p class="text-3xl font-bold text-indigo-700">₹{{ number_format($metrics['total_accrued_commission'], 0) }}</p>
                <p class="mt-1 text-xs text-gray-500">Total Commission</p>
            </div>
        </div>

        {{-- Commission breakdown --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl bg-white border border-gray-200 p-4 text-center">
                <p class="text-lg font-semibold text-amber-600">₹{{ number_format($metrics['pending_commission'], 0) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Pending Approval</p>
            </div>
            <div class="rounded-xl bg-white border border-gray-200 p-4 text-center">
                <p class="text-lg font-semibold text-blue-600">₹{{ number_format($metrics['approved_commission'], 0) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Approved</p>
            </div>
            <div class="rounded-xl bg-white border border-gray-200 p-4 text-center">
                <p class="text-lg font-semibold text-green-700">₹{{ number_format($metrics['paid_commission'], 0) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Paid Out</p>
            </div>
        </div>

        {{-- Recent leads --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Recent Leads</h2>
                <a href="{{ route('agent-portal.leads.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
            </div>
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-xs font-medium text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Counsellor</th>
                        <th class="px-4 py-2 text-left">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($recentLeads as $lead)
                    <tr>
                        <td class="px-4 py-2 font-medium text-gray-900">{{ $lead->fullName() }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700">{{ $lead->status->label() }}</span>
                        </td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $lead->assignedCounsellor?->name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $lead->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 text-xs">No leads yet. Submit your first lead!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="text-center">
            <a href="{{ route('agent-portal.leads.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Submit New Lead
            </a>
        </div>
    </div>
</x-layouts.agent-portal-app>
