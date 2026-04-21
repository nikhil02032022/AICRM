<?php

declare(strict_types=1);

namespace App\Enums\CRM\Payments;

// BRD: CRM-FM-001, CRM-FM-002 — Pre-admission fee categories
enum FeeType: string
{
    case APPLICATION = 'application';
    case SEAT_BOOKING = 'seat_booking';
    case TUITION_ADVANCE = 'tuition_advance';

    public function label(): string
    {
        return match ($this) {
            self::APPLICATION => 'Application Fee',
            self::SEAT_BOOKING => 'Seat Booking Fee',
            self::TUITION_ADVANCE => 'Tuition Advance',
        };
    }
}
