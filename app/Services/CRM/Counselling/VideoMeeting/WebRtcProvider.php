<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling\VideoMeeting;

use App\Models\CRM\CounsellingSession;

// BRD: CRM-EC-018 — WebRTC stub; native media relay deferred to Phase 2
// Returns a placeholder in-app route; no external API call
final class WebRtcProvider implements VideoMeetingProviderInterface
{
    public function generateLink(CounsellingSession $session): string
    {
        return route('crm.webrtc.room', ['session' => $session->getKey()]);
    }
}
