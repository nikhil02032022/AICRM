<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-SA-007 — Category tags for workflow automation templates
enum WorkflowTemplateCategory: string
{
    case LEAD_NURTURE           = 'lead_nurture';
    case APPLICATION_FOLLOWUP   = 'application_followup';
    case RE_ENGAGEMENT          = 're_engagement';
    case ONBOARDING             = 'onboarding';
    case EVENT_PROMOTION        = 'event_promotion';
    case GENERAL                = 'general';

    public function label(): string
    {
        return match ($this) {
            self::LEAD_NURTURE         => 'Lead Nurture',
            self::APPLICATION_FOLLOWUP => 'Application Follow-up',
            self::RE_ENGAGEMENT        => 'Re-Engagement',
            self::ONBOARDING           => 'Onboarding',
            self::EVENT_PROMOTION      => 'Event Promotion',
            self::GENERAL              => 'General',
        };
    }

    /** @return array<string, string> */
    public static function optionsForSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
