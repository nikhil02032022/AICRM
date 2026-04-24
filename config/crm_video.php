<?php

declare(strict_types=1);

// BRD: CRM-EC-018 — Video meeting provider configuration
// Set 'provider' to 'zoom', 'google_meet', 'webrtc', or 'none'
// For 'zoom': set zoom_room_url via Admin → System Config (institution-scoped)
// For 'google_meet': complete OAuth2 flow via Admin → Integrations (google_meet channel)
return [
    'provider' => env('CRM_VIDEO_PROVIDER', 'none'),
];
