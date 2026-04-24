<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling\VideoMeeting;

use App\Models\CRM\CounsellingSession;

// BRD: CRM-EC-018 — Strategy interface for video meeting link generation
interface VideoMeetingProviderInterface
{
    /**
     * Generate and return a meeting URL for the given counselling session.
     *
     * @throws \RuntimeException when provider is not configured or credentials are missing
     */
    public function generateLink(CounsellingSession $session): string;
}
