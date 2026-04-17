<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Offer Letter</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 0 20px; color: #333; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 24px; margin-bottom: 20px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.85em; font-weight: bold; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-generated { background: #dbeafe; color: #1e40af; }
        .badge-sent { background: #d1fae5; color: #065f46; }
        .badge-accepted { background: #d1fae5; color: #065f46; }
        .badge-declined { background: #fee2e2; color: #991b1b; }
        .badge-expired { background: #f3f4f6; color: #6b7280; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 6px; font-size: 1em; border: none; cursor: pointer; text-decoration: none; }
        .btn-accept { background: #059669; color: #fff; }
        .btn-decline { background: #dc2626; color: #fff; }
        .alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .alert-info { background: #dbeafe; border: 1px solid #93c5fd; color: #1e40af; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .checklist { margin: 8px 0; }
        .checklist li { margin: 4px 0; }
        .verified { color: #059669; }
        .unverified { color: #dc2626; }
    </style>
</head>
<body>
    <h1>Your Offer Letter</h1>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if (session('info'))
        <div class="alert-info">{{ session('info') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert-error">{{ $errors->first('error') }}</div>
    @endif

    <div class="card">
        <p><strong>Dear {{ $lead->full_name }},</strong></p>
        <p>We are pleased to inform you about your offer for admission.</p>

        <table style="width:100%; border-collapse: collapse; margin-top: 12px;">
            <tr><td style="padding: 6px 0; width: 40%;"><strong>Status</strong></td>
                <td><span class="badge badge-{{ $offer->status }}">{{ ucfirst($offer->status) }}</span></td></tr>
            <tr><td style="padding: 6px 0;"><strong>Programme</strong></td>
                <td>{{ $application->programme->name ?? '—' }}</td></tr>
            <tr><td style="padding: 6px 0;"><strong>Offer Expires</strong></td>
                <td>{{ $offer->expires_at?->format('d M Y') ?? '—' }}</td></tr>
            @if ($offer->conditional)
            <tr><td style="padding: 6px 0;"><strong>Offer Type</strong></td>
                <td>Conditional Offer</td></tr>
            @endif
        </table>

        @if ($offer->conditional && $offer->getRequiredDocuments())
            <h3 style="margin-top: 16px;">Required Documents</h3>
            <ul class="checklist">
                @foreach ($offer->getRequiredDocuments() as $doc)
                    @php $verified = $offer->getDocumentVerificationStatus()[$doc] ?? false; @endphp
                    <li class="{{ $verified ? 'verified' : 'unverified' }}">
                        {{ $verified ? '✓' : '✗' }} {{ ucwords(str_replace('_', ' ', $doc)) }}
                    </li>
                @endforeach
            </ul>
            @if (! $offer->allDocumentsVerified())
                <p style="color: #92400e; background: #fef3c7; padding: 10px; border-radius: 4px;">
                    Your offer acceptance is pending verification of the above documents by our admissions team.
                </p>
            @endif
        @endif
    </div>

    @if ($offer->isAccepted())
        <div class="alert-success">
            <strong>Offer Accepted</strong> — Thank you! Your acceptance was recorded on {{ $offer->acceptance_recorded_at?->format('d M Y, h:i A') }}.
        </div>
    @elseif ($offer->isDeclined())
        <div class="alert-info">
            <strong>Offer Declined</strong> — You have declined this offer.
        </div>
    @elseif ($offer->isExpired())
        <div class="alert-error">
            <strong>Offer Expired</strong> — This offer is no longer valid.
        </div>
    @elseif ($canAccept)
        <h2>Respond to Your Offer</h2>
        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
            <form method="POST" action="{{ route('portal.offers.accept', $token) }}">
                @csrf
                <textarea name="notes" placeholder="Any notes (optional)" style="width: 100%; margin-bottom: 8px; padding: 8px; border-radius: 4px; border: 1px solid #ddd;"></textarea>
                <button type="submit" class="btn btn-accept"
                    onclick="return confirm('Are you sure you want to ACCEPT this offer?')">
                    Accept Offer
                </button>
            </form>
            <form method="POST" action="{{ route('portal.offers.decline', $token) }}">
                @csrf
                <textarea name="reason" placeholder="Reason for declining (optional)" style="width: 100%; margin-bottom: 8px; padding: 8px; border-radius: 4px; border: 1px solid #ddd;"></textarea>
                <button type="submit" class="btn btn-decline"
                    onclick="return confirm('Are you sure you want to DECLINE this offer?')">
                    Decline Offer
                </button>
            </form>
        </div>
    @endif

    <p style="margin-top: 32px; font-size: 0.85em; color: #6b7280;">
        If you have questions, please contact our admissions team. This link is confidential and should not be shared.
    </p>
</body>
</html>
