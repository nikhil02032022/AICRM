<x-layouts.portal-guest title="Your Offer Letter">
    <x-slot:heading>Your Offer Letter</x-slot:heading>

    <div class="space-y-6">

        {{-- Application summary --}}
        <div class="rounded-lg border border-gray-200 p-5 space-y-3">
            <p class="text-gray-700">
                <span class="font-semibold">Dear {{ $lead->full_name }},</span><br />
                We are pleased to inform you about your offer for admission.
            </p>

            <dl class="divide-y divide-gray-100 text-sm">
                <div class="flex justify-between py-2">
                    <dt class="font-medium text-gray-600">Status</dt>
                    <dd>
                        @php
                            $badgeClass = match ($offer->status) {
                                'accepted'  => 'bg-green-100 text-green-800',
                                'declined'  => 'bg-red-100 text-red-800',
                                'expired'   => 'bg-gray-100 text-gray-600',
                                'sent'      => 'bg-green-50 text-green-700',
                                'generated' => 'bg-blue-100 text-blue-800',
                                default     => 'bg-yellow-100 text-yellow-800',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                            {{ ucfirst($offer->status) }}
                        </span>
                    </dd>
                </div>

                <div class="flex justify-between py-2">
                    <dt class="font-medium text-gray-600">Programme</dt>
                    <dd class="text-gray-900">{{ $application->programme->name ?? '—' }}</dd>
                </div>

                <div class="flex justify-between py-2">
                    <dt class="font-medium text-gray-600">Offer Expires</dt>
                    <dd class="text-gray-900">{{ $offer->expires_at?->format('d M Y') ?? '—' }}</dd>
                </div>

                @if ($offer->conditional)
                    <div class="flex justify-between py-2">
                        <dt class="font-medium text-gray-600">Offer Type</dt>
                        <dd class="text-gray-900">Conditional Offer</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Conditional offer document checklist --}}
        @if ($offer->conditional && $offer->getRequiredDocuments())
            <div class="rounded-lg border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Required Documents</h3>
                <ul class="space-y-2">
                    @foreach ($offer->getRequiredDocuments() as $doc)
                        @php $verified = $offer->getDocumentVerificationStatus()[$doc] ?? false; @endphp
                        <li class="flex items-center gap-2 text-sm">
                            @if ($verified)
                                <svg class="h-4 w-4 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span class="text-green-700">{{ ucwords(str_replace('_', ' ', $doc)) }}</span>
                            @else
                                <svg class="h-4 w-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span class="text-red-600">{{ ucwords(str_replace('_', ' ', $doc)) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if (! $offer->allDocumentsVerified())
                    <p class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 px-3 py-2 text-xs text-yellow-800">
                        Your offer acceptance is pending verification of the above documents by our admissions team.
                    </p>
                @endif
            </div>
        @endif

        {{-- Response actions --}}
        @if ($offer->isAccepted())
            <div class="rounded-md bg-green-50 border border-green-200 p-4">
                <p class="text-sm font-medium text-green-800">
                    Offer Accepted — Thank you! Your acceptance was recorded on
                    {{ $offer->acceptance_recorded_at?->format('d M Y, h:i A') }}.
                </p>
            </div>

        @elseif ($offer->isDeclined())
            <div class="rounded-md bg-gray-50 border border-gray-200 p-4">
                <p class="text-sm text-gray-700">You have declined this offer.</p>
            </div>

        @elseif ($offer->isExpired())
            <div class="rounded-md bg-red-50 border border-red-200 p-4">
                <p class="text-sm font-medium text-red-700">This offer is no longer valid.</p>
            </div>

        @elseif ($canAccept)
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-800">Respond to Your Offer</h3>

                <form method="POST" action="{{ route('portal.offers.accept', $token) }}">
                    @csrf
                    <textarea
                        name="notes"
                        placeholder="Any notes (optional)"
                        rows="2"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 mb-2"
                    ></textarea>
                    <button
                        type="submit"
                        onclick="return confirm('Are you sure you want to ACCEPT this offer?')"
                        class="w-full rounded-md portal-btn-primary px-4 py-2 text-sm font-semibold
                               transition-opacity focus:outline-none portal-ring-primary"
                    >
                        Accept Offer
                    </button>
                </form>

                <form method="POST" action="{{ route('portal.offers.decline', $token) }}">
                    @csrf
                    <textarea
                        name="reason"
                        placeholder="Reason for declining (optional)"
                        rows="2"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm
                               focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-400 mb-2"
                    ></textarea>
                    <button
                        type="submit"
                        onclick="return confirm('Are you sure you want to DECLINE this offer?')"
                        class="w-full rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm
                               font-semibold text-red-700 hover:bg-red-100 transition-colors"
                    >
                        Decline Offer
                    </button>
                </form>
            </div>
        @endif

        <p class="text-xs text-gray-400">
            If you have questions, please contact our admissions team.
            This link is confidential and should not be shared.
        </p>

    </div>

</x-layouts.portal-guest>
