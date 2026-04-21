<x-layouts.portal-app :title="'My Applications'" :applicant="$applicant">
    <x-slot:header>
        <h1 class="text-lg font-semibold text-gray-800">My Applications</h1>
    </x-slot:header>

    @if ($applications->isEmpty())
        {{-- Empty state --}}
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
            </svg>
            <h3 class="mt-4 text-sm font-medium text-gray-700">No applications yet</h3>
            <p class="mt-1 text-sm text-gray-500">Your submitted applications will appear here.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($applications as $item)
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
                    <div class="flex items-center justify-between px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-semibold text-gray-900 truncate">
                                {{ $app->programme?->name ?? 'Programme' }}
                            </h2>
                            <p class="mt-0.5 text-xs text-gray-500">
                                @if ($app->submitted_at)
                                    Submitted {{ $app->submitted_at->format('d M Y') }}
                                @else
                                    Not yet submitted
                                @endif
                                @if ($item['total_docs'] > 0)
                                    &middot;
                                    @if ($item['pending_docs'] === 0)
                                        <span class="text-green-600">Documents complete</span>
                                    @else
                                        <span class="text-orange-500">{{ $item['pending_docs'] }} doc{{ $item['pending_docs'] > 1 ? 's' : '' }} pending</span>
                                    @endif
                                @endif
                            </p>
                        </div>

                        <div class="ml-4 flex shrink-0 items-center gap-3">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badgeCss }}">
                                {{ $app->status->label() }}
                            </span>
                            <a href="{{ route('portal.applications.show', $app->uuid) }}"
                               class="inline-flex items-center gap-1 rounded-md portal-btn-primary px-3 py-1.5 text-xs font-medium transition-opacity">
                                View details
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Payment / offer letter quick stats --}}
                    @if ($item['payments']->isNotEmpty() || $app->currentOfferLetter)
                        <div class="border-t border-gray-100 px-5 py-3 flex flex-wrap gap-4 bg-gray-50">
                            @if ($item['payments']->isNotEmpty())
                                <span class="text-xs text-gray-600">
                                    <span class="font-medium text-gray-900">₹{{ number_format($item['total_paid'], 2) }}</span>
                                    confirmed · {{ $item['payments']->count() }} {{ Str::plural('payment', $item['payments']->count()) }}
                                </span>
                            @endif
                            @if ($app->currentOfferLetter)
                                <span class="text-xs portal-text-primary font-medium">
                                    Offer letter available
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</x-layouts.portal-app>
