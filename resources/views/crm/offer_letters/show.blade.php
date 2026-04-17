@extends('layouts.app')

@section('title', 'Offer Letter - ' . $offer->uuid)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Offer Letter</h1>
            <p class="text-gray-600 mt-2">{{ $application->programme->name }} - {{ $application->lead->full_name }}</p>
        </div>
        <span @class([
            'px-4 py-2 rounded-full text-sm font-semibold',
            'bg-green-100 text-green-800' => $offer->isAccepted(),
            'bg-red-100 text-red-800' => $offer->isDeclined(),
            'bg-yellow-100 text-yellow-800' => $offer->isExpired(),
            'bg-blue-100 text-blue-800' => !$offer->isAccepted() && !$offer->isDeclined() && !$offer->isExpired(),
        ])>
            {{ ucfirst($offer->status) }}
        </span>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Status</p>
            <p class="text-lg font-semibold text-gray-900">{{ ucfirst($offer->status) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Generated</p>
            <p class="text-lg font-semibold text-gray-900">{{ $offer->generated_at?->format('d M Y') ?? '—' }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Expires</p>
            <p class="text-lg font-semibold text-gray-900">{{ $offer->expires_at?->format('d M Y') ?? 'Never' }}</p>
        </div>
    </div>

    @if ($offer->pdf_path)
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
        <h3 class="font-semibold text-gray-900 mb-4">Download PDF</h3>
        <a href="{{ route('crm.offer_letters.download', $offer->uuid) }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            📥 Download Offer Letter PDF
        </a>
    </div>
    @endif

    @if ($offer->isValidForAcceptance())
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
        <h3 class="font-semibold text-gray-900 mb-4">Take Action</h3>
        <div class="flex gap-3">
            <a href="{{ route('crm.offer_letters.accept.form', $offer->uuid) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                ✓ Accept Offer
            </a>
            <a href="{{ route('crm.offer_letters.decline.form', $offer->uuid) }}" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                ✗ Decline Offer
            </a>
            <form action="{{ route('crm.offer_letters.send', $offer->uuid) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="channel" value="email">
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    ✉ Send via Email
                </button>
            </form>
        </div>
    </div>
    @elseif ($offer->isAccepted())
    <div class="bg-green-50 rounded-lg border border-green-200 p-6 mb-8">
        <p class="text-green-900">✓ This offer has been accepted on {{ $offer->acceptance_recorded_at?->format('d M Y H:i') }}</p>
    </div>
    @elseif ($offer->isDeclined())
    <div class="bg-red-50 rounded-lg border border-red-200 p-6 mb-8">
        <p class="text-red-900">✗ This offer was declined</p>
        @if ($offer->decline_reason)
        <p class="text-red-800 text-sm mt-2">Reason: {{ $offer->decline_reason }}</p>
        @endif
    </div>
    @elseif ($offer->isExpired())
    <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-6 mb-8">
        <p class="text-yellow-900">⚠ This offer has expired ({{ $offer->expires_at?->format('d M Y') }})</p>
    </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Offer Details</h3>
        @if($offer->isConditional())
            <div class="mb-6">
                <h4 class="font-semibold text-blue-800 mb-2">Conditional Offer Checklist</h4>
                <ul class="list-disc pl-6">
                    @forelse($offer->getRequiredDocuments() as $docType)
                        <li class="flex items-center gap-2">
                            @php $verified = $offer->getDocumentVerificationStatus()[$docType] ?? false; @endphp
                            <span class="inline-block w-3 h-3 rounded-full {{ $verified ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                            <span class="font-medium">{{ ucwords(str_replace('_', ' ', $docType)) }}</span>
                            <span class="ml-2 text-xs {{ $verified ? 'text-green-700' : 'text-gray-500' }}">{{ $verified ? 'Verified' : 'Pending' }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500">No required documents specified.</li>
                    @endforelse
                </ul>
                @if(!$offer->allDocumentsVerified())
                    <div class="mt-2 text-xs text-yellow-700">All documents must be verified before acceptance is allowed.</div>
                @endif
            </div>
        @endif
        <dl class="space-y-4">
            <div class="grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-600">Offer UUID</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $offer->uuid }}</dd>
            </div>
            <div class="grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-600">Applicant</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $offer->lead->full_name }} ({{ $offer->lead->email }})</dd>
            </div>
            <div class="grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-600">Programme</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $application->programme->name }}</dd>
            </div>
            <div class="grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-600">Sent Via</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $offer->sent_via ? strtoupper($offer->sent_via) : '—' }} on {{ $offer->sent_at?->format('d M Y H:i') ?? '—' }}</dd>
            </div>
            <div class="grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-600">Delivery Status</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $offer->delivery_status ?? '—' }}</dd>
            </div>
            @if($offer->delivery_message_id)
            <div class="grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-600">Delivery Message ID</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $offer->delivery_message_id }}</dd>
            </div>
            @endif
            @if ($offer->isAccepted())
            <div class="grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-600">Acceptance IP</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $offer->acceptance_ip }}</dd>
            </div>
            @endif
        </dl>
    </div>
</div>
@endsection
