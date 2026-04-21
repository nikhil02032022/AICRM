<?php

declare(strict_types=1);

namespace App\Enums\CRM\Payments;

// BRD: CRM-FM-003 — Supported payment gateway providers
enum GatewayProvider: string
{
    case RAZORPAY = 'razorpay';
    case PAYU = 'payu';
    case CCAVENUE = 'ccavenue';

    public function label(): string
    {
        return match ($this) {
            self::RAZORPAY => 'Razorpay',
            self::PAYU => 'PayU',
            self::CCAVENUE => 'CCAvenue',
        };
    }
}
