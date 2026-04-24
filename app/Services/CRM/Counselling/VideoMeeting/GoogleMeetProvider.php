<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling\VideoMeeting;

use App\Models\CRM\CounsellingSession;
use App\Models\CRM\IntegrationCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// BRD: CRM-EC-018 — Google Meet provider via Calendar API (OAuth2 service account or user token)
// Credentials stored in integration_credentials with channel = google_meet
final class GoogleMeetProvider implements VideoMeetingProviderInterface
{
    public function generateLink(CounsellingSession $session): string
    {
        $credential = IntegrationCredential::withoutGlobalScopes()
            ->where('institution_id', $session->institution_id)
            ->where('channel', 'google_meet')
            ->where('is_active', true)
            ->first();

        if ($credential === null) {
            throw new \RuntimeException('Google Meet credentials not configured for this institution.');
        }

        $accessToken = $credential->getCredential('access_token');

        if (empty($accessToken)) {
            throw new \RuntimeException('Google Meet access token missing in integration credentials.');
        }

        $requestId = Str::uuid()->toString();
        $startTime = $session->scheduled_at?->toRfc3339String() ?? now()->toRfc3339String();
        $endTime   = $session->scheduled_at?->addHour()->toRfc3339String() ?? now()->addHour()->toRfc3339String();

        $response = Http::withToken($accessToken)
            ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                'summary' => 'Counselling Session',
                'start' => ['dateTime' => $startTime, 'timeZone' => 'Asia/Kolkata'],
                'end' => ['dateTime' => $endTime, 'timeZone' => 'Asia/Kolkata'],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => $requestId,
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('GoogleMeetProvider: Calendar API error', [
                'status' => $response->status(),
                'session_id' => $session->getKey(),
            ]);
            throw new \RuntimeException('Google Calendar API returned an error: '.$response->status());
        }

        $hangoutLink = $response->json('hangoutLink') ?? $response->json('conferenceData.entryPoints.0.uri');

        if (empty($hangoutLink)) {
            throw new \RuntimeException('Google Calendar API did not return a Meet link.');
        }

        $credential->touchLastUsed();

        return $hangoutLink;
    }
}
