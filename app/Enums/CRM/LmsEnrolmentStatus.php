<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EI-010 — LMS enrolment trigger status lifecycle
enum LmsEnrolmentStatus: string
{
    case PENDING  = 'pending';
    case QUEUED   = 'queued';
    case ENROLLED = 'enrolled';
    case FAILED   = 'failed';
    case RETRYING = 'retrying';

    public function label(): string
    {
        return match($this) {
            self::PENDING  => 'Pending',
            self::QUEUED   => 'Queued',
            self::ENROLLED => 'Enrolled',
            self::FAILED   => 'Failed',
            self::RETRYING => 'Retrying',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING  => 'gray',
            self::QUEUED   => 'blue',
            self::ENROLLED => 'green',
            self::FAILED   => 'red',
            self::RETRYING => 'amber',
        };
    }
}
