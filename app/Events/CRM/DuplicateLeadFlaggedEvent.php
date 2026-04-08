<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-018 — Fired when DetectLeadDuplicatesJob identifies one or more
// potential duplicate leads. Listeners can notify the assigned counsellor,
// create a follow-up task, or push a real-time notification.
final class DuplicateLeadFlaggedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Lead                   $lead       The newly-created lead flagged as a duplicate
     * @param Collection<int, Lead>  $duplicates Existing leads that match on mobile/email or name+course
     * @param string                 $matchType  'mobile_email' | 'name_course' | 'both'
     */
    public function __construct(
        public readonly Lead       $lead,
        public readonly Collection $duplicates,
        public readonly string     $matchType,
    ) {}
}
