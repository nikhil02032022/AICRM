<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling;

use App\Enums\CRM\Counselling\VideoProvider;
use App\Jobs\CRM\Counselling\SendSessionVideoLinkJob;
use App\Models\CRM\CounsellingSession;
use App\Services\CRM\Counselling\VideoMeeting\GoogleMeetProvider;
use App\Services\CRM\Counselling\VideoMeeting\VideoMeetingProviderInterface;
use App\Services\CRM\Counselling\VideoMeeting\WebRtcProvider;
use App\Services\CRM\Counselling\VideoMeeting\ZoomProvider;
use Illuminate\Support\Facades\Log;

// BRD: CRM-EC-018 — Orchestrates meeting link generation using the configured provider strategy
final class VideoMeetingService
{
    public function __construct(
        private readonly GoogleMeetProvider $googleMeet,
        private readonly ZoomProvider $zoom,
        private readonly WebRtcProvider $webRtc,
    ) {}

    /**
     * Generate a meeting link for the session using the configured provider.
     * Persists meeting_link and meeting_provider on the session, then dispatches
     * SendSessionVideoLinkJob to notify the lead via email and WhatsApp.
     *
     * Falls back: GoogleMeet → Zoom → null (if both unconfigured).
     * WebRtc and None are handled directly without fallback.
     */
    public function generateLink(CounsellingSession $session): ?string
    {
        $providerKey = config('crm_video.provider', 'none');
        $provider    = VideoProvider::tryFrom((string) $providerKey) ?? VideoProvider::NONE;

        $link = match ($provider) {
            VideoProvider::GOOGLE_MEET => $this->tryProvider($this->googleMeet, $session, VideoProvider::ZOOM),
            VideoProvider::ZOOM => $this->tryProvider($this->zoom, $session, null),
            VideoProvider::WEB_RTC => $this->tryProvider($this->webRtc, $session, null),
            VideoProvider::NONE => null,
        };

        if ($link === null) {
            return null;
        }

        $session->update([
            'meeting_link' => $link,
            'meeting_provider' => $provider->value,
        ]);

        SendSessionVideoLinkJob::dispatch($session->getKey());

        return $link;
    }

    /**
     * Attempt a provider; on RuntimeException optionally fall through to $fallbackProvider.
     */
    private function tryProvider(
        VideoMeetingProviderInterface $provider,
        CounsellingSession $session,
        ?VideoProvider $fallback,
    ): ?string {
        try {
            return $provider->generateLink($session);
        } catch (\RuntimeException $e) {
            Log::warning('VideoMeetingService: provider failed, falling back', [
                'provider' => $provider::class,
                'error' => $e->getMessage(),
                'session_id' => $session->getKey(),
            ]);

            if ($fallback === VideoProvider::ZOOM) {
                return $this->tryProvider($this->zoom, $session, null);
            }

            return null;
        }
    }
}
