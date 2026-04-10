<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LC-014 — Every lead must carry a mandatory Source field indicating the acquisition channel
enum LeadSource: string
{
    case GOOGLE_ADS = 'google_ads';
    case FACEBOOK = 'facebook';
    case INSTAGRAM = 'instagram';  // BRD: CRM-LC-004 — Meta distinguishes FB vs Instagram
    case WALK_IN = 'walk_in';
    case REFERRAL = 'referral';
    case EDUCATION_PORTAL = 'education_portal';
    case WHATSAPP = 'whatsapp';
    case IVR = 'ivr';
    case EVENT = 'event';
    case WEBSITE_ORGANIC = 'website_organic';
    case CSV_IMPORT = 'csv_import';
    case API = 'api';
    case QR_CODE = 'qr_code';

    public function label(): string
    {
        return match ($this) {
            self::GOOGLE_ADS => 'Google Ads',
            self::FACEBOOK => 'Facebook',
            self::INSTAGRAM => 'Instagram',
            self::WALK_IN => 'Walk-In',
            self::REFERRAL => 'Referral',
            self::EDUCATION_PORTAL => 'Education Portal',
            self::WHATSAPP => 'WhatsApp',
            self::IVR => 'IVR',
            self::EVENT => 'Event',
            self::WEBSITE_ORGANIC => 'Website (Organic)',
            self::CSV_IMPORT => 'CSV Import',
            self::API => 'API',
            self::QR_CODE => 'QR Code',
        };
    }

    /** Returns true if this source channel typically carries UTM parameters. */
    public function supportsUtm(): bool
    {
        return in_array($this, [
            self::GOOGLE_ADS,
            self::FACEBOOK,
            self::INSTAGRAM,
            self::WEBSITE_ORGANIC,
        ], true);
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
