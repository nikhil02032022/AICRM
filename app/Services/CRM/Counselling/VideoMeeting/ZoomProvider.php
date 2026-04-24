<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling\VideoMeeting;

use App\Models\CRM\Admin\SystemConfig;
use App\Models\CRM\CounsellingSession;

// BRD: CRM-EC-018 — Zoom provider returns institution's personal meeting room URL (no API call)
// Admin sets the Zoom room URL in System Config under key 'zoom_room_url'
final class ZoomProvider implements VideoMeetingProviderInterface
{
    public function generateLink(CounsellingSession $session): string
    {
        $url = SystemConfig::forInstitution($session->institution_id)
            ->where('key', 'zoom_room_url')
            ->value('value');

        if (empty($url)) {
            throw new \RuntimeException('Zoom room URL not configured. Set zoom_room_url in System Config.');
        }

        return (string) $url;
    }
}
