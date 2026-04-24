{{-- BRD: CRM-AL-002 — Alumni Referral Campaigns list --}}
<x-layouts.crm title="Alumni Referral Campaigns">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Alumni Referral Campaigns</h1>
        <p class="mt-1 text-sm text-gray-500">Create and manage referral campaigns. Generate unique codes for alumni to share with prospective students.</p>
    </x-slot:header>

    <x-slot:headerActions>
        @can('create', \App\Models\CRM\Alumni\AlumniReferralCampaign::class)
        <a href="{{ route('crm.alumni.referral.campaigns.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
            </svg>
            New Campaign
        </a>
        @endcan
    </x-slot:headerActions>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800 shadow-sm">
        <svg class="h-5 w-5 flex-shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
        </svg>
        <span class="flex-1 font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Table --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        @if($campaigns->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg class="mb-4 h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
            </svg>
            <p class="text-base font-semibold text-gray-700">No referral campaigns yet</p>
            <p class="mt-1 text-sm text-gray-500">Create a campaign to start generating referral codes for alumni.</p>
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Campaign</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reward</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Duration</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Codes</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach($campaigns as $campaign)
                <tr class="transition-colors hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="text-sm font-semibold text-gray-900">{{ $campaign->name }}</p>
                        @if($campaign->description)
                        <p class="mt-0.5 text-xs text-gray-500 line-clamp-1">{{ $campaign->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-700">{{ $campaign->reward_type->label() }}</p>
                        @if($campaign->reward_value)
                        <p class="text-xs text-gray-500">₹{{ number_format($campaign->reward_value, 0) }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        {{ $campaign->start_date->format('d M Y') }}
                        @if($campaign->end_date)
                        <span class="text-gray-400"> → </span>{{ $campaign->end_date->format('d M Y') }}
                        @else
                        <span class="text-gray-400"> → </span><span class="text-xs text-gray-400">No end</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('crm.alumni.referral.codes.index', $campaign) }}"
                           class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 tabular-nums">
                            {{ number_format($campaign->codes_count) }}
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1.5 rounded-full {{ $campaign->status->bgColour() }} px-2.5 py-1 text-xs font-medium {{ $campaign->status->textColour() }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $campaign->status->dotColour() }}"></span>
                            {{ $campaign->status->label() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @can('update', $campaign)
                            <a href="{{ route('crm.alumni.referral.campaigns.edit', $campaign) }}"
                               class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">
                                Edit
                            </a>
                            @if($campaign->status->value === 'draft' || $campaign->status->value === 'paused')
                            <form method="POST" action="{{ route('crm.alumni.referral.campaigns.activate', $campaign) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 rounded-md bg-green-100 px-2.5 py-1.5 text-xs font-medium text-green-700 hover:bg-green-200">
                                    Activate
                                </button>
                            </form>
                            @elseif($campaign->status->value === 'active')
                            <form method="POST" action="{{ route('crm.alumni.referral.campaigns.pause', $campaign) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2.5 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-200">
                                    Pause
                                </button>
                            </form>
                            @endif
                            @endcan
                            <a href="{{ route('crm.alumni.referral.codes.index', $campaign) }}"
                               class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-100">
                                Codes
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($campaigns->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $campaigns->links() }}
        </div>
        @endif
        @endif
    </div>
</x-layouts.crm>
