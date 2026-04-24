<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LC-003 — Google Ads Lead Form Extensions webhook
// BRD: CRM-LC-004 — Meta Lead Ads (Facebook + Instagram) webhook
// BRD: CRM-LC-008 — Education portal imports (Shiksha, CollegeDekho, Careers360, Collegedunia)
// BRD: CRM-LC-012 — Bulk CSV/Excel upload
// BRD: CRM-SA-010 — Integration credential channel identifier
enum IntegrationChannel: string
{
    case GOOGLE_ADS = 'google_ads';
    case META = 'meta';
    case SHIKSHA = 'shiksha';
    case COLLEGE_DEKHO = 'college_dekho';
    case CAREERS360 = 'careers360';
    case COLLEGEDUNIA = 'collegedunia';
    case BULK_CSV = 'bulk_csv';
    // BRD: CRM-LC-020 — A2A ERP Student Master outbound lookup channel
    case ERP_A2A = 'erp_a2a';
    // BRD: CRM-EC-018 — Google Meet OAuth2 credentials for video counselling
    case GOOGLE_MEET = 'google_meet';

    public function label(): string
    {
        return match ($this) {
            self::GOOGLE_ADS => 'Google Ads Lead Form Extensions',
            self::META => 'Meta Lead Ads (Facebook / Instagram)',
            self::SHIKSHA => 'Shiksha',
            self::COLLEGE_DEKHO => 'CollegeDekho',
            self::CAREERS360 => 'Careers360',
            self::COLLEGEDUNIA => 'Collegedunia',
            self::BULK_CSV => 'Bulk CSV / Excel Upload',
            self::ERP_A2A => 'A2A ERP Student Master',
            self::GOOGLE_MEET => 'Google Meet (Video Counselling)',
        };
    }

    /** Returns the LeadSource enum value that should be set for leads from this channel. */
    public function toLeadSource(): LeadSource
    {
        return match ($this) {
            self::GOOGLE_ADS => LeadSource::GOOGLE_ADS,
            self::META => LeadSource::FACEBOOK,
            self::SHIKSHA, self::COLLEGE_DEKHO,
            self::CAREERS360, self::COLLEGEDUNIA => LeadSource::EDUCATION_PORTAL,
            self::BULK_CSV => LeadSource::CSV_IMPORT,
        };
    }

    /** Webhook channels — require HMAC signature verification. */
    public function isWebhook(): bool
    {
        return in_array($this, [
            self::GOOGLE_ADS,
            self::META,
            self::SHIKSHA,
            self::COLLEGE_DEKHO,
            self::CAREERS360,
            self::COLLEGEDUNIA,
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

    /** Portal-only channels — used for CSV import source tagging. */
    public static function portalCases(): array
    {
        return [
            self::SHIKSHA,
            self::COLLEGE_DEKHO,
            self::CAREERS360,
            self::COLLEGEDUNIA,
        ];
    }
}
