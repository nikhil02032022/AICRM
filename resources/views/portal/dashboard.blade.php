<x-layouts.portal-app :title="'Dashboard'" :applicant="$applicant">
    <x-slot:header>
        <h1 class="text-lg font-semibold text-gray-800">Dashboard</h1>
    </x-slot:header>

    {{-- Welcome --}}
    <div class="mb-6">
        <p class="text-gray-600 text-sm">
            Welcome back, <span class="font-medium text-gray-900">{{ $lead->first_name }}</span>.
            Here's a summary of your applications.
        </p>
    </div>

    @if ($applicationData->isEmpty())
        {{-- Empty state --}}
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
            </svg>
            <h3 class="mt-4 text-sm font-medium text-gray-700">No applications yet</h3>
            <p class="mt-1 text-sm text-gray-500">Your applications will appear here once submitted.</p>
        </div>
    @else
        {{-- Application cards --}}
        <div class="space-y-6">
            @foreach ($applicationData as $item)
                @php
                    $app     = $item['application'];
                    $colour  = $app->status->badgeColour();
                    $colours = [
                        'blue'    => 'bg-blue-100 text-blue-800',
                        'cyan'    => 'bg-cyan-100 text-cyan-800',
                        'yellow'  => 'bg-yellow-100 text-yellow-800',
                        'purple'  => 'bg-purple-100 text-purple-800',
                        'green'   => 'bg-green-100 text-green-800',
                        'orange'  => 'bg-orange-100 text-orange-800',
                        'emerald' => 'bg-emerald-100 text-emerald-800',
                        'slate'   => 'bg-slate-100 text-slate-700',
                        'red'     => 'bg-red-100 text-red-800',
                    ];
                    $badgeCss = $colours[$colour] ?? 'bg-gray-100 text-gray-700';
                @endphp

                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    {{-- Card header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold text-gray-900 truncate">
                                {{ $app->programme?->name ?? 'Programme' }}
                            </h2>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Application
                                @if ($app->submitted_at)
                                    · Submitted {{ $app->submitted_at->format('d M Y') }}
                                @endif
                            </p>
                        </div>
                        <span class="ml-4 shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $badgeCss }}">
                            {{ $app->status->label() }}
                        </span>
                    </div>

                    {{-- Stats row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">

                        {{-- Documents --}}
                        <div class="px-5 py-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Documents</p>
                            @if ($item['total_docs'] > 0)
                                @if ($item['pending_docs'] === 0)
                                    <p class="mt-1 text-sm font-medium text-green-700">All submitted ✓</p>
                                @else
                                    <p class="mt-1 text-sm font-semibold text-orange-600">
                                        {{ $item['pending_docs'] }} pending
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $item['total_docs'] - $item['pending_docs'] }}/{{ $item['total_docs'] }} required submitted
                                    </p>
                                @endif
                            @else
                                <p class="mt-1 text-sm text-gray-400">No checklist</p>
                            @endif
                        </div>

                        {{-- Payments --}}
                        <div class="px-5 py-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Payments</p>
                            @if ($item['payments']->isNotEmpty())
                                <p class="mt-1 text-sm font-semibold text-gray-900">
                                    ₹{{ number_format($item['total_paid'], 2) }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $item['payments']->count() }} {{ Str::plural('payment', $item['payments']->count()) }} confirmed
                                </p>
                            @else
                                <p class="mt-1 text-sm text-gray-400">No payments yet</p>
                            @endif
                        </div>

                        {{-- Offer letter --}}
                        <div class="px-5 py-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Offer Letter</p>
                            @if ($app->currentOfferLetter)
                                <p class="mt-1 text-sm font-medium portal-text-primary">
                                    Available
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ ucfirst($app->currentOfferLetter->status) }}
                                </p>
                            @else
                                <p class="mt-1 text-sm text-gray-400">Not issued yet</p>
                            @endif
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Upcoming Appointments --}}
    <div class="mt-8">
        <h2 class="text-base font-semibold text-gray-800 mb-3">Upcoming Appointments</h2>

        @if ($upcomingAppointments->isEmpty())
            <p class="text-sm text-gray-400">No upcoming appointments scheduled.</p>
        @else
            <div class="space-y-3">
                @foreach ($upcomingAppointments as $session)
                    <div class="flex items-start gap-4 rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm">
                        <div class="shrink-0 rounded-lg bg-indigo-50 p-2 text-center w-14">
                            <p class="text-xs font-bold text-indigo-700 uppercase leading-none">
                                {{ $session->scheduled_at->format('M') }}
                            </p>
                            <p class="text-xl font-bold text-indigo-800 leading-tight">
                                {{ $session->scheduled_at->format('d') }}
                            </p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $session->session_type->value ?? ucfirst($session->session_type->value ?? 'Counselling') }}
                                session
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $session->scheduled_at->format('l, d M Y · g:i A') }}
                                @if ($session->counsellor)
                                    · with {{ $session->counsellor->name }}
                                @endif
                            </p>
                            @if ($session->mode)
                                <span class="inline-block mt-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    {{ ucfirst($session->mode) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</x-layouts.portal-app>
