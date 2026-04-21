<?php

declare(strict_types=1);

namespace App\Enums\CRM\Tasks;

// BRD: CRM-TF-005 — Task completion requires a disposition/outcome selection
enum TaskDisposition: string
{
    case ReachedInterested    = 'reached_interested';
    case ReachedNotInterested = 'reached_not_interested';
    case NotReachable         = 'not_reachable';
    case CallBackRequested    = 'call_back_requested';
    case WrongNumber          = 'wrong_number';
    case NumberInvalid        = 'number_invalid';
    case MeetingScheduled     = 'meeting_scheduled';
    case DocumentsReceived    = 'documents_received';

    public function label(): string
    {
        return match ($this) {
            self::ReachedInterested    => 'Reached — Interested',
            self::ReachedNotInterested => 'Reached — Not Interested',
            self::NotReachable         => 'Not Reachable',
            self::CallBackRequested    => 'Call Back Requested',
            self::WrongNumber          => 'Wrong Number',
            self::NumberInvalid        => 'Number Invalid',
            self::MeetingScheduled     => 'Meeting Scheduled',
            self::DocumentsReceived    => 'Documents Received',
        };
    }

    public function isPositive(): bool
    {
        return match ($this) {
            self::ReachedInterested, self::MeetingScheduled, self::DocumentsReceived => true,
            default => false,
        };
    }
}
