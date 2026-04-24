<?php

declare(strict_types=1);

namespace App\Enums\CRM\Alumni;

// BRD: CRM-AL-002 — Types of rewards offered through alumni referral campaigns
enum ReferralRewardType: string
{
    case GiftVoucher = 'gift_voucher';
    case FeeWaiver   = 'fee_waiver';
    case Recognition = 'recognition';

    public function label(): string
    {
        return match ($this) {
            self::GiftVoucher => 'Gift Voucher',
            self::FeeWaiver   => 'Fee Waiver',
            self::Recognition => 'Recognition',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GiftVoucher => '🎁',
            self::Recognition => '🏆',
            self::FeeWaiver   => '💸',
        };
    }

    /** @return array<string, string> */
    public static function optionsForSelect(): array
    {
        return array_column(
            array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases()),
            'label',
            'value',
        );
    }
}
