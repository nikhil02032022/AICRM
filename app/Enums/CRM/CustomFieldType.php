<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EC-005 — Supported custom field data types
enum CustomFieldType: string
{
    case TEXT     = 'text';
    case TEXTAREA = 'textarea';
    case NUMBER   = 'number';
    case DATE     = 'date';
    case SELECT   = 'select';
    case CHECKBOX = 'checkbox';
    case URL      = 'url';

    public function label(): string
    {
        return match ($this) {
            self::TEXT     => 'Single Line Text',
            self::TEXTAREA => 'Multi-Line Text',
            self::NUMBER   => 'Number',
            self::DATE     => 'Date',
            self::SELECT   => 'Dropdown (Select)',
            self::CHECKBOX => 'Checkbox (Yes/No)',
            self::URL      => 'URL',
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
