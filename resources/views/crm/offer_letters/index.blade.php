@extends('layouts.app')

@section('title', 'Offer Letters - ' . $application->programme->name)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Offer Letters</h1>
            <p class="text-gray-600 mt-2">Application: {{ $application->uuid }}</p>
        </div>
        @can('create', \App\Models\CRM\OfferLetter::class)
        <a href="{{ route('crm.applications.offers.create', $application->uuid) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Generate Offer Letter
        </a>
        @endcan
    </div>

    @if ($offers->count() > 0)
        <div class="space-y-4">
            @foreach ($offers as $offer)
            <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Offer #{{ $offer->uuid }}</h3>
                        <p class="text-sm text-gray-600">Generated: {{ $offer->generated_at?->format('d M Y H:i') ?? 'Pending' }}</p>
                    </div>
                    <span @class([
                        'px-3 py-1 rounded-full text-sm font-medium',
                        'bg-green-100 text-green-800' => $offer->isAccepted(),
                        'bg-red-100 text-red-800' => $offer->isDeclined(),
                        'bg-yellow-100 text-yellow-800' => $offer->isExpired(),
                        'bg-blue-100 text-blue-800' => !$offer->isAccepted() && !$offer->isDeclined() && !$offer->isExpired(),
                    ])>
                        {{ $offer->status }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-4 text-sm mb-6">
                    <div>
                        <span class="text-gray-600">Status</span>
                        <p class="font-semibold text-gray-900">{{ ucfirst($offer->status) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Expires</span>
                        <p class="font-semibold text-gray-900">{{ $offer->expires_at?->format('d M Y') ?? 'Never' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Sent Via</span>
                        <p class="font-semibold text-gray-900">{{ $offer->sent_via ? strtoupper($offer->sent_via) : '—' }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('crm.offer_letters.show', $offer->uuid) }}" class="px-3 py-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                        View Details
                    </a>
                    @if ($offer->isValidForAcceptance())
                        <a href="{{ route('crm.offer_letters.accept.form', $offer->uuid) }}" class="px-3 py-2 bg-green-50 text-green-600 hover:bg-green-100 rounded-lg">
                            Accept
                        </a>
                        <a href="{{ route('crm.offer_letters.decline.form', $offer->uuid) }}" class="px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg">
                            Decline
                        </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{ $offers->links() }}
    @else
        <div class="bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
            <p class="text-gray-600 mb-4">No offer letters have been generated yet.</p>
            @can('create', \App\Models\CRM\OfferLetter::class)
            <a href="{{ route('crm.applications.offers.create', $application->uuid) }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Generate First Offer Letter
            </a>
            @endcan
        </div>
    @endif
</div>
@endsection
