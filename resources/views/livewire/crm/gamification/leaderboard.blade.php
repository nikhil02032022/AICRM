{{-- BRD: CRM-EC-010 — Leaderboard Livewire Component --}}
<div class="bg-white rounded-lg shadow-md">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Leaderboard</h3>
        <p class="text-sm text-gray-600 mt-1">Top performers for {{ $periodType->label() }} period</p>
    </div>

    <div class="overflow-x-auto">
        @if($leaderboard->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rank
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Counsellor
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Points
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Conversions
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rate
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Trend
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($leaderboard as $entry)
                        <tr class="{{ $entry->user_id === $currentUserId ? 'bg-indigo-50' : 'hover:bg-gray-50' }} transition-colors">
                            {{-- Rank --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($entry->rank === 1)
                                        <span class="text-2xl">🥇</span>
                                    @elseif($entry->rank === 2)
                                        <span class="text-2xl">🥈</span>
                                    @elseif($entry->rank === 3)
                                        <span class="text-2xl">🥉</span>
                                    @else
                                        <span class="text-sm font-medium text-gray-900">#{{ $entry->rank }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Counsellor Name --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(substr($entry->user->name, 0, 2)) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $entry->user->name }}
                                            @if($entry->user_id === $currentUserId)
                                                <span class="ml-2 text-xs text-indigo-600 font-semibold">(You)</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $entry->user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Total Points --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                                    {{ number_format($entry->total_points) }}
                                </span>
                            </td>

                            {{-- Conversions --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-medium text-gray-900">{{ $entry->leads_converted }}</span>
                            </td>

                            {{-- Conversion Rate --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm text-gray-900">{{ number_format($entry->conversion_rate, 1) }}%</span>
                            </td>

                            {{-- Trend --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($entry->rank_change > 0)
                                    <span class="inline-flex items-center text-green-600">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-xs font-medium">{{ $entry->rank_change }}</span>
                                    </span>
                                @elseif($entry->rank_change < 0)
                                    <span class="inline-flex items-center text-red-600">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-xs font-medium">{{ abs($entry->rank_change) }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-gray-500">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-6 py-12 text-center">
                <div class="text-6xl mb-4">📊</div>
                <p class="text-sm text-gray-600">No leaderboard data available</p>
                <p class="text-xs text-gray-500 mt-1">Start performing activities to appear on the leaderboard!</p>
            </div>
        @endif
    </div>

    @if($leaderboard->count() > 10)
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <p class="text-sm text-gray-600 text-center">
                Showing top {{ $leaderboard->count() }} counsellors
            </p>
        </div>
    @endif
</div>
