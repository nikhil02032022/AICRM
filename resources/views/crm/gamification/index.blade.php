{{-- BRD: CRM-EC-010 — Counsellor Performance Gamification Dashboard --}}
<x-layouts.crm>
    <x-slot:header>Performance Dashboard</x-slot:header>

    <x-slot:headerActions>
        {{-- Period Selector --}}
        <div class="flex items-center gap-2">
            <label for="period" class="text-sm font-medium text-gray-700">Period:</label>
            <select 
                id="period" 
                name="period"
                onchange="window.location.href = '{{ route('crm.gamification.index') }}?period=' + this.value"
                class="rounded-md border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            >
                <option value="daily" {{ $periodType->value === 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="weekly" {{ $periodType->value === 'weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ $periodType->value === 'monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="quarterly" {{ $periodType->value === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                <option value="yearly" {{ $periodType->value === 'yearly' ? 'selected' : '' }}>Yearly</option>
            </select>
        </div>
    </x-slot:headerActions>

    {{-- Page intro --}}
    <div class="mb-6">
        <p class="text-sm text-gray-600">Track your achievements and compete with your peers</p>
    </div>

    {{-- Stats Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Rank Card --}}
            <div class="bg-gradient-to-br from-indigo-500 to-violet-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 rounded-lg p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                </div>
                <div class="text-sm font-medium opacity-90">Your Rank</div>
                <div class="text-4xl font-bold mt-2">
                    @if($counsellorRank > 0)
                        #{{ $counsellorRank }}
                    @else
                        --
                    @endif
                </div>
                @if($currentScore && $currentScore->rank_change ?? 0 != 0)
                    <div class="mt-2 text-sm">
                        @if($currentScore->rank_change > 0)
                            <span class="text-green-200">↑ +{{ $currentScore->rank_change }}</span>
                        @else
                            <span class="text-red-200">↓ {{ $currentScore->rank_change }}</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Total Points Card --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-indigo-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                </div>
                <div class="text-sm font-medium text-gray-600">Total Points</div>
                <div class="text-4xl font-bold text-gray-900 mt-2">
                    {{ $currentScore->total_points ?? 0 }}
                </div>
            </div>

            {{-- Conversion Rate Card --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <div class="text-sm font-medium text-gray-600">Conversion Rate</div>
                <div class="text-4xl font-bold text-gray-900 mt-2">
                    {{ number_format($currentScore->conversion_rate ?? 0, 1) }}%
                </div>
                <div class="mt-2 text-sm text-gray-500">
                    {{ $currentScore->leads_converted ?? 0 }} / {{ $currentScore->leads_handled ?? 0 }} leads
                </div>
            </div>

            {{-- Streak Card --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-amber-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        </svg>
                    </div>
                </div>
                <div class="text-sm font-medium text-gray-600">Active Streak</div>
                <div class="text-4xl font-bold text-gray-900 mt-2">
                    {{ $currentScore->streak_days ?? 0 }}
                </div>
                <div class="mt-2 text-sm text-gray-500">days</div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Leaderboard - Takes 2 columns --}}
            <div class="lg:col-span-2">
                @livewire('crm.gamification.leaderboard', [
                    'leaderboard' => $leaderboard,
                    'periodType' => $periodType,
                    'currentUserId' => auth()->id()
                ])
            </div>

            {{-- Sidebar: Badges & KPIs --}}
            <div class="space-y-6">
                {{-- Badges --}}
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Badges</h3>
                    @if($badges->count() > 0)
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($badges as $counsellorBadge)
                                <div 
                                    class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg border-2 border-{{ $counsellorBadge->badge->color }}-200 hover:border-{{ $counsellorBadge->badge->color }}-400 transition-colors cursor-pointer group"
                                    x-data="{ tooltip: false }"
                                    @mouseenter="tooltip = true"
                                    @mouseleave="tooltip = false"
                                >
                                    <div class="text-3xl mb-2">{{ $counsellorBadge->badge->icon ?? '🏆' }}</div>
                                    <div class="text-xs font-medium text-gray-700 text-center">{{ $counsellorBadge->badge->name }}</div>
                                    <div class="text-xs text-gray-500 mt-1">+{{ $counsellorBadge->points_earned }} pts</div>
                                    
                                    {{-- Tooltip --}}
                                    <div 
                                        x-show="tooltip" 
                                        x-cloak
                                        class="absolute z-10 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 mt-20 max-w-xs"
                                        style="display: none;"
                                    >
                                        {{ $counsellorBadge->badge->description }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-6xl mb-4">🎯</div>
                            <p class="text-sm text-gray-600">No badges earned yet</p>
                            <p class="text-xs text-gray-500 mt-1">Keep performing to unlock achievements!</p>
                        </div>
                    @endif
                </div>

                {{-- KPI Summary --}}
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Calls Made</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $currentScore->calls_made ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Emails Sent</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $currentScore->emails_sent ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Meetings Scheduled</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $currentScore->meetings_scheduled ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Applications Submitted</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $currentScore->applications_submitted ?? 0 }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Avg Response Time</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $currentScore->avg_response_time_minutes ?? 0 }} min</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Satisfaction Score</span>
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($currentScore->student_satisfaction_score ?? 0, 1) }} / 5.0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-layouts.crm>