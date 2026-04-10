<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-018 — Call outcome dispositions
enum CallDisposition: string
{
    case INTERESTED    = 'INTERESTED';
    case NOT_INTERESTED = 'NOT_INTERESTED';
    case CALL_BACK     = 'CALL_BACK';
    case WRONG_NUMBER  = 'WRONG_NUMBER';
    case NOT_REACHABLE = 'NOT_REACHABLE';
    case NUMBER_INVALID = 'NUMBER_INVALID';
    case VOICEMAIL     = 'VOICEMAIL';
    case BUSY          = 'BUSY';

    public function label(): string
    {
        return match($this) {
            self::INTERESTED     => 'Interested',
            self::NOT_INTERESTED => 'Not Interested',
            self::CALL_BACK      => 'Call Back Requested',
            self::WRONG_NUMBER   => 'Wrong Number',
            self::NOT_REACHABLE  => 'Not Reachable',
            self::NUMBER_INVALID => 'Number Invalid',
            self::VOICEMAIL      => 'Voicemail',
            self::BUSY           => 'Busy',
        };
    }

    /** Auto-create a follow-up task when this disposition is selected */
    public function requiresFollowUpTask(): bool
    {
        return $this === self::CALL_BACK;
    }

    /** @return array<string, string> */
    public static function optionsForSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
